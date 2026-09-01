<div class="form-group">
    <label>Kiểu hiển thị</label>
    {!! Form::select('style', [
        'slider' => 'Slider',
        'grid'   => 'Grid'
    ], $attributes['style'] ?? 'slider', ['class' => 'form-control']) !!}
</div>