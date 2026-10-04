@props(['modal' => null, 'bag' => 'default'])
<?php $b = $errors->getBag($bag); ?>
@if ($b->any() && ($modal === null || old('_modal') === $modal))
  <div class="form-error" data-clear>{{ $b->first() }}</div>
@endif
