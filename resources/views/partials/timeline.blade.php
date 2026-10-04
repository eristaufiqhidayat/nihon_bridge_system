<div class="tl-scroll"><div class="timeline-h">
  @foreach (\App\Support\Catalog::STAGES as $i => $st)
    <?php $cls = $i < $student->stage ? 'done' : ($i === $student->stage ? 'current' : ''); ?>
    <div class="tl-step {{ $cls }}"><div class="tl-circ">{{ $i < $student->stage ? '✓' : $i + 1 }}</div><div class="tl-label">{{ $st }}<span>{{ $student->stageLabel($i) }}</span></div></div>
  @endforeach
</div></div>
