<?php
/**
 * Created by PhpStorm.
 * User: j5521
 * Date: 2019/1/15
 * Time: 下午 06:57
 */

namespace l552121229\laravelAdminExtMapSearch;

use Encore\Admin\Form\Field;

class MapSearch extends Field
{
    protected $view = 'laravel-admin-map-search::map-search';
    /**
     * @var array
     */
    protected static $css = [
        '/vendor/laravel-admin/AdminLTE/plugins/select2/select2.min.css',
    ];

    /**
     * @var array
     */
    protected static $js = [
        '/vendor/laravel-admin/AdminLTE/plugins/select2/select2.full.min.js',
    ];

    /**
     * @var array
     */
    protected $groups = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Set options.
     *
     * @param array|callable|string $options
     *
     * @return $this|mixed
     */
    public function options($options = [])
    {
        // remote options
        if (is_string($options)) {
            // reload selected
            if (class_exists($options) && in_array(Model::class, class_parents($options))) {
                return $this->model(...func_get_args());
            }

            return $this->loadRemoteOptions(...func_get_args());
        }

        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        if (is_callable($options)) {
            $this->options = $options;
        } else {
            $this->options = (array) $options;
        }

        return $this;
    }

    /**
     * @param array $groups
     */

    /**
     * Set option groups.
     *
     * eg: $group = [
     *        [
     *        'label' => 'xxxx',
     *        'options' => [
     *            1 => 'foo',
     *            2 => 'bar',
     *            ...
     *        ],
     *        ...
     *     ]
     *
     * @param array $groups
     *
     * @return $this
     */
    public function groups(array $groups)
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Load options for other select on change.
     *
     * @param string $field
     * @param string $sourceUrl
     * @param string $idField
     * @param string $textField
     *
     * @return $this
     */
    public function load($field, $sourceUrl, $idField = 'id', $textField = 'text')
    {
        if (Str::contains($field, '.')) {
            $field = $this->formatName($field);
            $class = str_replace(['[', ']'], '_', $field);
        } else {
            $class = $field;
        }

        $script = <<<EOT
$(document).off('change', "{$this->getElementClassSelector()}");
$(document).on('change', "{$this->getElementClassSelector()}", function () {
    var target = $(this).closest('.fields-group').find(".$class");
    $.get("$sourceUrl?q="+this.value, function (data) {
        target.find("option").remove();
        $(target).select2({
            data: $.map(data, function (d) {
                d.id = d.$idField;
                d.text = d.$textField;
                return d;
            })
        }).trigger('change');
    });
});
EOT;

        Admin::script($script);

        return $this;
    }

    /**
     * Load options for other selects on change.
     *
     * @param string $fields
     * @param string $sourceUrls
     * @param string $idField
     * @param string $textField
     *
     * @return $this
     */
    public function loads($fields = [], $sourceUrls = [], $idField = 'id', $textField = 'text')
    {
        $fieldsStr = implode('.', $fields);
        $urlsStr = implode('^', $sourceUrls);
        $script = <<<EOT
var fields = '$fieldsStr'.split('.');
var urls = '$urlsStr'.split('^');

var refreshOptions = function(url, target) {
    $.get(url).then(function(data) {
        target.find("option").remove();
        $(target).select2({
            data: $.map(data, function (d) {
                d.id = d.$idField;
                d.text = d.$textField;
                return d;
            })
        }).trigger('change');
    });
};

$(document).off('change', "{$this->getElementClassSelector()}");
$(document).on('change', "{$this->getElementClassSelector()}", function () {
    var _this = this;
    var promises = [];

    fields.forEach(function(field, index){
        var target = $(_this).closest('.fields-group').find('.' + fields[index]);
        promises.push(refreshOptions(urls[index] + "?q="+ _this.value, target));
    });

    $.when(promises).then(function() {
        console.log('开始更新其它select的选择options');
    });
});
EOT;

        Admin::script($script);

        return $this;
    }

    /**
     * Load options from current selected resource(s).
     *
     * @param string $model
     * @param string $idField
     * @param string $textField
     *
     * @return $this
     */
    public function model($model, $idField = 'id', $textField = 'name')
    {
        if (!class_exists($model)
            || !in_array(Model::class, class_parents($model))
        ) {
            throw new \InvalidArgumentException("[$model] must be a valid model class");
        }

        $this->options = function ($value) use ($model, $idField, $textField) {
            if (empty($value)) {
                return [];
            }

            $resources = [];

            if (is_array($value)) {
                if (Arr::isAssoc($value)) {
                    $resources[] = array_get($value, $idField);
                } else {
                    $resources = array_column($value, $idField);
                }
            } else {
                $resources[] = $value;
            }

            return $model::find($resources)->pluck($textField, $idField)->toArray();
        };

        return $this;
    }

    /**
     * Load options from remote.
     *
     * @param string $url
     * @param array  $parameters
     * @param array  $options
     *
     * @return $this
     */
    protected function loadRemoteOptions($url, $parameters = [], $options = [])
    {
        $ajaxOptions = [
            'url' => $url.'?'.http_build_query($parameters),
        ];

        $ajaxOptions = json_encode(array_merge($ajaxOptions, $options));

        $this->script = <<<EOT

$.ajax($ajaxOptions).done(function(data) {

  var select = $("{$this->getElementClassSelector()}");

  select.select2({data: data});
  
  var value = select.data('value') + '';
  
  if (value) {
    value = value.split(',');
    select.select2('val', value);
  }
});

EOT;

        return $this;
    }

    /**
     * Load options from ajax results.
     *
     * @param string $url
     * @param $idField
     * @param $textField
     *
     * @return $this
     */
    public function ajax($url, $idField = 'id', $textField = 'text')
    {
        $configs = array_merge([
            'allowClear'         => true,
            'placeholder'        => $this->label,
            'minimumInputLength' => 1,
        ], $this->config);

        $configs = json_encode($configs);
        $configs = substr($configs, 1, strlen($configs) - 2);

        $this->script = <<<EOT

$("{$this->getElementClassSelector()}").select2({
  ajax: {
    url: "$url",
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        q: params.term,
        page: params.page
      };
    },
    processResults: function (data, params) {
      params.page = params.page || 1;

      return {
        results: $.map(data.data, function (d) {
                   d.id = d.$idField;
                   d.text = d.$textField;
                   return d;
                }),
        pagination: {
          more: data.next_page_url
        }
      };
    },
    cache: true
  },
  $configs,
  escapeMarkup: function (markup) {
      return markup;
  }
});

EOT;

        return $this;
    }

    /**
     * Set config for select2.
     *
     * all configurations see https://select2.org/configuration/options-api
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return $this
     */
    public function config($key, $val)
    {
        $this->config[$key] = $val;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        switch (config('admin.map_provider')) {
            case 'google':
                $this->script = <<<script
var mapSearch = function() {
    var mapSearchResult = [],
        searchData = [],
        searchStatus = false,
        latlngBounds = {},
        SearchMap = {},
        master_marker = {},
        autoComplete = {},
        
        geocoder = new google.maps.Geocoder(),
        google_map_places_service = new google.maps.places.PlacesService(map),
        
        eventSelect = $("select{$this->getElementClassSelector()}");
        
        eventSelect.select2({
            allowClear: true,
            placeholder: '$this->label',
            ajax: {
                transport: function (params, success) {
                    SearchMap.success = success;
                    searchKeyword(params.data.term);
                }
            }
        });
        
        eventSelect.on("select2:select", function (e) {
            var index = e.params.data.index;
            var latLng = mapSearchResult[index].geometry.location;
            
            marker.setPosition(latLng);
            map.setCenter(latLng);
        });
        
        //配置搜索相关
        var searchKeyword = function(keyWords) {
            google_map_places_service.textSearch({
                location:map_center,
                query:keyWords,
                radius: '500',
                type: []
            }, function(data, status) {
                if (status === 'OK') {
                    var searchData = [];
                    mapSearchResult = data;
                    searchStatus = true;
                    for (var i in data) {
                        var poi = data[i];
                        var address = poi.formatted_address;
                        if(typeof(address) === 'undefined'){
                            address = poi.name;
                        }
                        searchData.push({
                            id: address,
                            text: poi.name,
                            index: i
                        });
                    }
                    SearchMap.success({
                        results: searchData
                    });
                }
            });
        };
        
        //配置点击
        marker.addListener('position_changed', function() {
            if (!searchStatus) {
                var position = this.getPosition();
                searchAddress(position);
            }
            searchStatus = false;
        });
        
        function searchAddress(position){
            geocoder.geocode({location:position}, function(data, status) {
                if (status === 'OK') {
                    var formatted_address = data[0].formatted_address;
                    eventSelect.empty();
                    eventSelect.append(new Option(formatted_address, formatted_address));
                }
            });
        }
        searchAddress(marker.getPosition());
};
script;
                break;
            case 'tencent':
                $this->script = <<<script
                
var searchService,
searchServiceBygps,
markers,
mapSearchResult,
searchData = [];
var latlngBounds,
SearchMap = {};

var eventSelect = $("select{$this->getElementClassSelector()}");

eventSelect.select2({
    allowClear: true,
    placeholder: '$this->label',
    ajax: {
        transport: function (params, success) {
            SearchMap.success = success;
            searchKeyword(params.data.term);
        }
    }
});

eventSelect.on("select2:select", function (e) {
    clearOverlays(markers);
    var index = e.params.data.index;
    var latLng = mapSearchResult[index].latLng;
    latlngBounds.extend(latLng);
    var marker = new qq.maps.Marker({
        map: map,
        draggable: true,
        position: latLng
    });
    qq.maps.event.addListener(map, 'click', function(event) {
        marker.setPosition(event.latLng);
    });
    //监听地址的变动
    qq.maps.event.addListener(marker, 'position_changed', function(event) {
        var position = marker.getPosition();
        lat.val(position.getLat());
        lng.val(position.getLng());
    });
    //直接变动地址
    var position = marker.getPosition();
    lat.val(position.getLat());
    lng.val(position.getLng());

    marker.setTitle(1);

    markers.push(marker);
    //调整地图视野
    map.fitBounds(latlngBounds);
});

//清除地图上的marker
function clearOverlays(overlays) {
    var overlay;
    while (overlay = overlays.pop()) {
        overlay.setMap(null);
    }
}

//设置搜索的范围和关键字等属性
function searchKeyword(word) {
    //根据输入的城市设置搜索范围
    // searchService.setLocation("北京");
    //根据输入的关键字在搜索范围内检索
    searchService.search(word);
}

//配置搜索相关
(function(){
    latlngBounds = new qq.maps.LatLngBounds();
    //设置Poi检索服务，用于本地检索、周边检索
    searchService = new qq.maps.SearchService({
        //设置搜索范围为全国
        location: "全国",
        //设置搜索页码为0
        pageIndex: 0,
        //设置每页的结果数为15
        pageCapacity: 15,
        //设置动扩大检索区域。默认值true，会自动检索指定城市以外区域。
        autoExtend: true,
        //检索成功的回调函数
        complete: function(results) {
            //设置回调函数参数
            var pois = results.detail.pois;
            if (typeof(pois) !== 'undefined') {
                mapSearchResult = pois;
                searchData = [];
                clearOverlays(markers);
                for (var i = 0, l = pois.length; i < l; i++) {
                    var poi = pois[i];
                    var address = poi.address;
                    if(typeof(poi.address) === 'undefined'){
                        address = poi.name;
                    }
                    searchData.push({
                        id: address,
                        text: poi.name,
                        index: i
                    });
//                    eventSelect.append(new Option(poi.name, address));
                }
                SearchMap.success({
                    results: searchData
                });

                //扩展边界范围，用来包含搜索到的Poi点
                var latLng = pois[0].latLng;
                latlngBounds.extend(latLng);
                var marker = new qq.maps.Marker({
                    map: map,
                    draggable: true,
                    position: latLng
                });

                qq.maps.event.addListener(map, 'click', function(event) {
                    marker.setPosition(event.latLng);
                });

                qq.maps.event.addListener(marker, 'position_changed', function(event) {
                    var position = marker.getPosition();
                    lat.val(position.getLat());
                    lng.val(position.getLng());
                });

                var position = marker.getPosition();
                lat.val(position.getLat());
                lng.val(position.getLng());

                marker.setTitle(1);

                markers.push(marker);

                //调整地图视野
                map.fitBounds(latlngBounds);
            }
        },
        //若服务请求失败，则运行以下函数
        error: function() {
            console.log('没有结果');
        }
    });
    
    searchServiceBygps = new qq.maps.Geocoder({
        //检索成功的回调函数
        complete: function(results) {
            //设置回调函数参数
            var address = results.detail.address;
            if (typeof(address) !== 'undefined') {
                eventSelect.empty();
                eventSelect.append(new Option(address, address));
            }
        },
        //若服务请求失败，则运行以下函数
        error: function() {
            console.log('没有结果');
        }
    });
})();
script;
                break;
            case 'amap' :
            default :
                $this->script = <<<script
var searchService = [],
searchServiceBygps = [],
mapSearchResult = [],
searchData = [],
latlngBounds = {},
SearchMap = {},
master_marker = {},
autoComplete = {},
geocoder = {},

eventSelect = $("select{$this->getElementClassSelector()}");

eventSelect.select2({
    allowClear: true,
    placeholder: '$this->label',
    ajax: {
        transport: function (params, success) {
            SearchMap.success = success;
            searchKeyword(params.data.term);
        }
    }
});

eventSelect.on("select2:select", function (e) {
    var index = e.params.data.index;
    var latLng = mapSearchResult[index].location;
    
    master_marker.setPosition(latLng);
    map.setCenter(latLng);
    map.setFitView(master_marker);
});

//配置搜索相关
var searchKeyword = function(keyWords) {
    autoComplete.search(keyWords, function(status, result) {
        if(status === 'complete') {
            // 搜索成功时，result即是对应的匹配数据
            searchComplete(result.tips);
        }
    });
};
var searchComplete = function (pois) {
    mapSearchResult = pois;
    var searchData = [];
    for (var i in pois) {
        var poi = pois[i];
        var address = poi.district;
        if(typeof(address) === 'undefined'){
            address = poi.name;
        }
        searchData.push({
            id: address,
            text: poi.name,
            index: i
        });
    }
    SearchMap.success({
        results: searchData
    });
};

var geocoderfunction = function(position) {
    geocoder.getAddress([position.lng, position.lat], function(status, result) {
            if (status === 'complete' && result.regeocode) {
                var address = result.regeocode.formattedAddress;
                eventSelect.empty();
                eventSelect.append(new Option(address, address));
            }else{
                eventSelect.empty();
                eventSelect.append(new Option('未知位置', '未知位置'));
            }
            
            map_click = false;
        });
};

$(function() {
    AMap.plugin('AMap.Geocoder', function() {
        geocoder = new AMap.Geocoder();
        
        AMap.event.addListener(map, 'moveend', function() {
            if(map_click) {
                geocoderfunction(map_center);
            }
        });
    });
    
    AMap.plugin('AMap.Autocomplete', function(){
        // 实例化Autocomplete
        autoComplete = new AMap.Autocomplete();
    });
});
script;
                break;
        }

        if ($this->options instanceof \Closure) {
            if ($this->form) {
                $this->options = $this->options->bindTo($this->form->model());
            }

            $this->options(call_user_func($this->options, $this->value));
        }

        $this->options = array_filter($this->options, 'strlen');

        $this->addVariables([
            'options' => $this->options,
            'groups'  => $this->groups,
        ]);

        $this->attribute('data-value', implode(',', (array) $this->value()));

        return parent::render();
    }
}