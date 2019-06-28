<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">

    <label for="map-{{$id}}" class="{{$viewClass['label']}} control-label">{{$label}}<br /><a href='' onclick='(function(){
        $.get("", {"map_change" : true}, function() {location.reload();});
    })();return false;'>切换地图</a></label>

<div class="{{$viewClass['field']}}">

    @include('admin::form.error')

    <input class="{{$class}}" type="hidden" name="{{$name}}"/>

    <select id="map-{{$id}}" class="form-control {{$class}}" style="width: 100%;" name="{{$name}}" {!! $attributes !!} >
        <option class="mapSearch" value=""></option>
        @foreach($options as $select => $option)
            <option value="{{$select}}" {{ $select == old($column, $value) ?'selected':'' }}>{{$option}}</option>
        @endforeach
    </select>
    @include('admin::form.help-block')

</div>
</div>