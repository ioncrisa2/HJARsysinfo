<div class="dsb-stats-grid">
    @foreach($this->getStats() as $stat)
    <div class="dsb-stat-card dsb-stat-{{ $stat['color'] }}">
        <div class="dsb-stat-top">
            <div class="dsb-stat-icon-wrap">
                <x-filament::icon :icon="$stat['icon']" class="dsb-stat-icon" />
            </div>
            @if($stat['trend'] !== null)
                <span class="dsb-stat-trend {{ $stat['trend'] >= 0 ? 'up' : 'down' }}">
                    {{ $stat['trend'] >= 0 ? '↑' : '↓' }} {{ abs($stat['trend']) }}%
                </span>
            @endif
        </div>
        <div class="dsb-stat-value">{{ $stat['value'] }}</div>
        <div class="dsb-stat-label">{{ $stat['label'] }}</div>
        <div class="dsb-stat-sub">{{ $stat['sub'] }}</div>
    </div>
    @endforeach
</div>
