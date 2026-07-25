<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\EligibilityResult;
use App\Models\Pembanding;
use App\Services\Peruntukan\PeruntukanGroupService;

class EligibilityService
{
    public function __construct(
        private readonly ScoringAttributeResolver $resolver,
        private readonly ScoringProfile $profile,
        private readonly PeruntukanGroupService $groups,
    ) {}

    public function evaluate(Pembanding $reference, Pembanding $candidate): EligibilityResult
    {
        $referenceBasis = $this->resolver->marketBasis($reference);
        $candidateBasis = $this->resolver->marketBasis($candidate);

        if ($referenceBasis && $candidateBasis && $referenceBasis !== $candidateBasis) {
            return new EligibilityResult(false, 'excluded', ['Market basis sale dan rent berbeda.']);
        }

        $warnings = [];
        if (! $referenceBasis) {
            $warnings[] = 'Market basis objek acuan tidak diketahui.';
        }
        if ($referenceBasis && ! $candidateBasis) {
            $warnings[] = 'Market basis kandidat tidak diketahui.';
        }

        $referenceDate = $reference->getAttribute('reference_date');
        $candidateDate = $candidate->getAttribute('tanggal_data');
        if ($referenceDate && $candidateDate && $candidateDate->isAfter($referenceDate)) {
            return new EligibilityResult(
                false,
                'excluded',
                ['Tanggal kandidat berada setelah tanggal acuan penilaian.'],
            );
        }

        $referenceZoning = $this->resolver->peruntukan($reference);
        $candidateZoning = $this->resolver->peruntukan($candidate);
        $fallback = (bool) ($candidate->getAttribute('is_fallback') ?? false);
        $tier = $fallback ? 'fallback' : (
            (! $referenceBasis || ! $candidateBasis) ? 'secondary' : 'primary'
        );
        $reasons = [];

        if ($referenceZoning && ! $candidateZoning) {
            if (! $fallback) {
                $tier = 'secondary';
            }
            $reasons[] = 'Peruntukan kandidat tidak tersedia.';
            $warnings[] = 'Peruntukan kandidat tidak tersedia.';
        } elseif ($referenceZoning && $candidateZoning && $referenceZoning !== $candidateZoning) {
            $approved = $this->profile->similarity(
                'peruntukan_similarity',
                $referenceZoning,
                $candidateZoning,
                -1,
            ) >= 0;
            $sameGroup = $this->groups->getGroup($referenceZoning) !== null
                && $this->groups->getGroup($referenceZoning) === $this->groups->getGroup($candidateZoning);
            $tier = $fallback
                ? 'fallback'
                : (($approved && $tier === 'primary') ? 'primary' : 'secondary');
            $reasons[] = $approved
                ? 'Peruntukan menggunakan substitusi yang dikonfigurasi.'
                : ($sameGroup ? 'Peruntukan hanya sama pada tingkat grup.' : 'Peruntukan berbeda.');
        }

        $referenceObject = $this->resolver->objectType($reference);
        $candidateObject = $this->resolver->objectType($candidate);

        if ($referenceObject && ! $candidateObject && $tier === 'primary') {
            $tier = 'secondary';
            $reasons[] = 'Jenis objek kandidat tidak tersedia.';
            $warnings[] = 'Jenis objek kandidat tidak tersedia.';
        } elseif ($referenceObject
            && $candidateObject
            && $referenceObject !== $candidateObject
            && $tier === 'primary') {
            $tier = 'secondary';
            $reasons[] = 'Jenis objek tidak exact match.';
        }

        return new EligibilityResult(true, $tier, $reasons, $warnings);
    }
}
