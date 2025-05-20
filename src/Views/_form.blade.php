@php
    $modelName = str_replace('App\\SoapDian\\Models\\', '', str_replace('App\\Models\\', '', $model));
    $model = new $model();
    $model::booted();
    $module = $model->getTable();
    $fields = $model::getFields();
    $groupedFields = $model::getGroupedFields();
    if (count($groupedFields) <= 0) {
        $groupedFields = ['basic_data' => $fields];
    } else {
        foreach ($groupedFields as $key => $group) {
            foreach ($group as $field) {
                $fields[] = $field;
            }
        }
    }
    $columns = $model::getColumns();
    $isUpdate = true;
    if (!isset($modelRow)) {
        $modelRow = new $model();
        $modelRow->presets();
        $isUpdate = false;
    }
    $fieldTypes = $model::getFieldTypes();
    $fieldWidths = $model::getFieldWidths();
    $crudInModal = $model::getCrudInModal();
    $withImage = $model::getWithImage();
    $withPhoto = $model::getWithPhoto();
    $withFile = $model::getWithFile();
    $withFiles = $model::getWithFiles();
    $withImages = $model::getWithImages();
@endphp
<div class="container-fluid">
    <form action="{{ url(str_replace(['/create', '/edit'], '', Request::path())) }}" method="POST" id="form-main"
        enctype="multipart/form-data">
        <input type="hidden" name="_method" value="{{ $isUpdate ? 'PATCH' : 'POST' }}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="text-capitalize breadcrumb-title">{{ $title }}</h4>
                    <div class="breadcrumb-action justify-content-center flex-wrap">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                {{-- <li class="breadcrumb-item"><a href="#"><i class="uil uil-estate"></i>Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Form</li> --}}
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">

                @if (View::exists('pages.forms.' . strtolower($modelName)))
                    <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                        <div class="row">
                            @include('pages.forms.' . strtolower($modelName))
                        </div>
                    </div>
                @else
                    @if (View::exists('pages.forms.' . strtolower($modelName) . '_pre'))
                        {{-- <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                            <div class="row"> --}}
                        @include('pages.forms.' . strtolower($modelName) . '_pre')
                        {{-- </div>
                        </div> --}}
                    @endif
                    @foreach ($groupedFields as $group => $groupFields)
                        <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <h6>{{ trans('general.form_groups.' . $group) }}</h6>
                                </div>
                            </div>
                            <div class="row">
                                @foreach ($groupFields as $field)
                                    <div
                                        class="{{ array_key_exists($field, $fieldWidths) ? 'form-group col-md-' . $fieldWidths[$field] : 'form-group mb-20 ' }}">
                                        <label>{{ trans($module . '.fields.' . $field) }}</label>
                                        @if (array_key_exists($field, $fieldTypes))
                                            @switch($fieldTypes[$field]['type'])
                                                @case('select')
                                                    <select class="form-control select"
                                                        placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                        {{ Table::getReadOnly($field, $model) }}
                                                        {{ Table::getRequired($field, $model) }} name="{{ $field }}"
                                                        id="{{ $field }}">
                                                        @foreach ($fieldTypes[$field]['data'] as $key => $value)
                                                            <option id="{{ $field }}_{{ $key }}"
                                                                value="{{ $key }}"
                                                                @if ($key == $modelRow->{$field}) @selected(true) @endif>
                                                                {{ $value }}</option>
                                                        @endforeach
                                                    </select>
                                                @break

                                                @case('multiselect')
                                                    <select class="form-control select foreign" multiple
                                                        placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                        {{ Table::getReadOnly($field, $model) }}
                                                        {{ Table::getRequired($field, $model) }} name="{{ $field }}[]"
                                                        id="{{ $field }}">
                                                    </select>
                                                @break

                                                @case('foreign')
                                                    <select class="form-control select foreign"
                                                        placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                        {{ Table::getReadOnly($field, $model) }}
                                                        {{ Table::getRequired($field, $model) }} name="{{ $field }}"
                                                        id="{{ $field }}">
                                                    </select>
                                                @break

                                                @case('boolean')
                                                    <select class="form-control select"
                                                        placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                        {{ Table::getReadOnly($field, $model) }}
                                                        {{ Table::getRequired($field, $model) }} name="{{ $field }}"
                                                        id="{{ $field }}">
                                                        <option id="{{ $field }}_true" value="1"
                                                            @if ($modelRow->{$field} == 1) @selected(true) @endif>
                                                            Si
                                                        </option>
                                                        <option id="{{ $field }}_false" value="0"
                                                            @if ($modelRow->{$field} == 0) @selected(true) @endif>
                                                            No
                                                        </option>
                                                    </select>
                                                @break

                                                @case('textarea')
                                                    <textarea class="form-control text-editor" placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                        {{ Table::getReadOnly($field, $model) }} {{ Table::getRequired($field, $model) }} name="{{ $field }}"
                                                        id="{{ $field }}">{{ $modelRow->{$field} }}</textarea>
                                                @break

                                                @case('date')
                                                    <input type="{{ Table::getInputType($field, $model) }}"
                                                        class="form-control"
                                                        placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                        {{ Table::getReadOnly($field, $model) }}
                                                        {{ Table::getRequired($field, $model) }} name="{{ $field }}"
                                                        id="{{ $field }}"
                                                        value="{{ $modelRow->{$field}?->format('Y-m-d') }}">
                                                @break

                                                @default
                                                    @if ($fieldTypes[$field]['format'] ?? '' == 'currency')
                                                        <input type="text" step="any"
                                                            class="form-control input-format-price"
                                                            placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                            {{ Table::getReadOnly($field, $model) }}
                                                            {{ Table::getRequired($field, $model) }}
                                                            name="{{ $field }}" id="{{ $field }}"
                                                            value="{{ $modelRow->{$field} }}">
                                                    @else
                                                        <input type="{{ Table::getInputType($field, $model) }}" step="any"
                                                            class="form-control"
                                                            placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                            {{ Table::getReadOnly($field, $model) }}
                                                            {{ Table::getRequired($field, $model) }}
                                                            name="{{ $field }}" id="{{ $field }}"
                                                            value="{{ $field == 'password' ? '' : $modelRow->{$field} }}">
                                                    @endif
                                            @endswitch
                                        @else
                                            <input type="{{ Table::getInputType($field, $model) }}"
                                                class="form-control"
                                                placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                {{ Table::getReadOnly($field, $model) }}
                                                {{ Table::getRequired($field, $model) }} name="{{ $field }}"
                                                id="{{ $field }}"
                                                value="{{ $field == 'password' ? '' : $modelRow->{$field} }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @if (View::exists('pages.forms.' . strtolower($modelName) . '_post'))
                        @include('pages.forms.' . strtolower($modelName) . '_post')
                    @endif
                @endif
                @if (View::exists('pages.partials.accounting._' . strtolower($modelName)))
                    <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h6>Configuracion Contable</h6>
                            </div>
                        </div>
                        <div class="row">
                            @include('pages.partials.accounting._' . strtolower($modelName))
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @if ($withPhoto)
            <div class="row form-group">
                <div class="col-md-12">
                    <div class="container-photo">
                        <span class="span-photo">FOTO EVIDENCIA</span>
                        <div class="photo1"></div>
                    </div>
                </div>
            </div>
        @endif
        @if ($withImage)
            <div class="row form-group">
                <div class="col-md-12">
                    <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                        <div class="row form-group">
                            <div class="col-md-12">
                                @foreach ($modelRow->files ?? [] as $file)
                                    <a target="_blank" href="{!! url('storage/' . $file->url) !!}">Ver/Descargar evidencia</a><br>
                                @endforeach
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                <input type="file" name="file"
                                    @if (!$isUpdate) @required(true) @endif>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if ($withImages)
            <div class="row form-group">
                <div class="col-md-12">
                    <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Imagenes</h6>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                @foreach ($modelRow->files ?? [] as $file)
                                    <a target="_blank" href="{!! url('storage/' . $file->url) !!}">Ver/Descargar evidencia</a><br>
                                @endforeach
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                <div class="input-images"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if ($withFiles)
            <div class="row form-group">
                <div class="col-md-12">
                    <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                        <div class="row form-group">
                            <div class="col-md-12">
                                @foreach ($modelRow->files ?? [] as $file)
                                    <a target="_blank" href="{!! url($file->url) !!}">Ver/Descargar Adjunto</a><br>
                                @endforeach
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                <input type="file" name="file[]" multiple>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if ($withFile)
            <div class="row form-group">
                <div class="col-md-12">
                    <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                        <div class="row form-group">
                            <div class="col-md-12">
                                <h5>Soporte/Evidencia</h5>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                @foreach ($modelRow->files ?? [] as $file)
                                    <a target="_blank" href="{!! url($file->url) !!}">Ver/Descargar evidencia</a><br>
                                @endforeach
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                <input type="file" name="evidence_file">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div
                    class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30 text-center">
                    <button type="button" class="btn btn-primary btn-submit mb-0">Guardar</button>
                    <button type="submit" class="hide hidden" id="btn-submit"></button>
                </div>
            </div>
        </div>
        @if ($withPhoto)
            <input type="hidden" name="photo1" id="photo1" />
            <input type="hidden" name="load-photo1" id="load-photo1" value="0" />
        @endif
        <input type="hidden" name="updated_at" value="{{ now() }}" />
    </form>
</div>
@section('javascript')
    @if ($withPhoto)
        <script src="{{ url('assets/js/camera-library.min.js') }}"></script>
    @endif
    @if ($withImages)
        <script src="{{ url('js/image-uploader.min.js') }}"></script>
    @endif
    <script>
        let attributes = {!! json_encode(
            array_values(array_unique(array_values(array_merge(array_values($columns), array_values($fields))))),
        ) !!};
        let isUpdate = {{ $isUpdate ? 'true' : 'false' }};
        $(document).ready(function() {

            @if ($withPhoto)
                $('.photo1').customWebCam({
                    canvasWidth: 400,
                    captureTimeoutInSeconds: 0,
                    canvasClass: "canvas-photo1",
                    idPhoto: "photo1"
                });
            @endif

            $(".btn-submit").click(function() {

                @if ($withPhoto)
                    $("#photo1").val(document.getElementById("canvas-photo1").toDataURL('image/png'));
                    if ($("#load-photo1").val() == 0) {
                        message('error', 'Se debe tomar la foto de evidencia.');
                        return false;
                    }
                @endif

                let canSave = true;

                if (!canSaveForm()) {
                    canSave = false;
                }

                if (!canSave) {
                    return false;
                }
                $("#cargando").show();
                $("#btn-submit").click();
            });

            @if (!$isUpdate)

                @foreach ($fields as $field)
                    @if (array_key_exists($field, $fieldTypes))
                        @if ($fieldTypes[$field]['type'] == 'foreign')
                            $("#{{ $field }}").off().select2({
                                minimumResultsForSearch: 0,
                                placeholder: "Selecciona un item",
                                allowClear: true,
                                ajax: {
                                    url: '{{ route($fieldTypes[$field]['route']) }}',
                                    data: function(params) {
                                        var query = {
                                            s: params.term,
                                            page: params.page || 1
                                        }
                                        return query;
                                    }
                                }
                            });
                        @elseif ($fieldTypes[$field]['type'] == 'multiselect')
                            $("#{{ $field }}").off().select2({
                                minimumResultsForSearch: 0,
                                placeholder: "Selecciona un item",
                                allowClear: true,
                                ajax: {
                                    url: '{{ route($fieldTypes[$field]['route']) }}',
                                    data: function(params) {
                                        var query = {
                                            s: params.term,
                                            page: params.page || 1
                                        }
                                        return query;
                                    }
                                }
                            });
                        @endif
                    @endif
                @endforeach
                attributes.forEach(function(item, i) {
                    if ($("#" + item).hasClass("select")) {
                        $("#" + item + ">option").removeAttr('selected');
                    } else {
                        if ($("#" + item).hasClass("text-editor")) {
                            $(".text-editor").trumbowyg('html', '');
                        } else {
                            //$("#" + item).val("");
                        }
                    }
                });
            @else
                let values = {!! json_encode($modelRow->attributesToArray()) !!};
                attributes.forEach(function(item, i) {
                    if ($("#" + item).hasClass("foreign")) {
                        @foreach ($fields as $field)
                            @if (array_key_exists($field, $fieldTypes))
                                @if ($fieldTypes[$field]['type'] == 'foreign')
                                    if (item == '{{ $field }}') {
                                        $("#{{ $field }}").off().select2({
                                            minimumResultsForSearch: 0,
                                            placeholder: "Selecciona un item",
                                            allowClear: true,
                                            ajax: {
                                                url: '{{ route($fieldTypes[$field]['route']) }}',
                                                data: function(params) {
                                                    var query = {
                                                        s: params.term,
                                                        page: params.page || 1
                                                    }
                                                    return query;
                                                }
                                            }
                                        });
                                        $.get('{{ url($fieldTypes[$field]['find_route']) }}/' + values[
                                            item], {}, function(json) {
                                            $("#" + item).append("<option value='" + json['id'] +
                                                "'>" +
                                                json[
                                                    '{{ $fieldTypes[$field]['foreign_field'] }}'
                                                ] +
                                                "</option>");
                                            $("#" + item).val(json['id']).trigger('change');
                                        });
                                    }
                                @elseif ($fieldTypes[$field]['type'] == 'multiselect')
                                    if (item == '{{ $field }}') {
                                        $("#{{ $field }}").off().select2({
                                            minimumResultsForSearch: 0,
                                            placeholder: "Selecciona un item",
                                            allowClear: true,
                                            ajax: {
                                                url: '{{ route($fieldTypes[$field]['route']) }}',
                                                data: function(params) {
                                                    var query = {
                                                        s: params.term,
                                                        page: params.page || 1
                                                    }
                                                    return query;
                                                }
                                            }
                                        });
                                        $.each(values[item], function(keyItem, itemSelect) {
                                            $.get('{{ url($fieldTypes[$field]['find_route']) }}/' +
                                                itemSelect, {},
                                                function(json) {
                                                    $("#" + item).append("<option value='" +
                                                        json[
                                                            'id'] + "'>" +
                                                        json['name'] +
                                                        "</option>");
                                                    $("#" + item).val(values[item]).trigger(
                                                        'change');
                                                });
                                        });
                                    }
                                @endif
                            @endif
                        @endforeach
                    }
                });
            @endif

            @if ($withImages)
                let preloaded = [
                    @foreach ($modelRow->images ?? [] as $image)
                        {
                            id: '{{ $image->id }}',
                            src: '{{ url($image->url) }}'
                        },
                    @endforeach
                ];

                $('.input-images').imageUploader({
                    preloaded: preloaded,
                    preloadedInputName: 'old',
                    label: 'Arrastra o selecciona las imagenes a cargar',
                    maxSize: 2 * 1024 * 1024,
                    maxFiles: 4
                });

                $(".delete-image").click(function() {
                    let id = $(this).parent().find('input').val();
                    if (id) {
                        $.ajax({
                            url: '{{ url('es/images') }}/' + id,
                            type: 'DELETE',
                            success: function(result) {
                                console.log(result);
                                return;
                            }
                        });
                    }
                });
            @endif


            $("#state").change(function() {
                $("#cargando").show();
                $.get('{{ url('ajax/departments') }}/' + $(this).val() + '/cities', {}, function(json) {
                    $("#city").html('');
                    $.each(json, function(key, item) {
                        $("#city").append("<option value='" + item['id'] + "'>" + item[
                            'name'] + "</option>");
                    });
                    $("#city").val($("#city option:first").val()).change();
                }).always(function() {
                    $("#cargando").hide();
                });
            });

        });

        $("#city").change(function() {
            $("#cargando").show();
            $.get('{{ url('ajax/cities') }}/' + $(this).val(), {}, function(json) {
                $("#postal_code").val(json['postalcode']);
            }).always(function() {
                $("#cargando").hide();
            });
        });
    </script>
@endsection

@section('css')
    <link type="text/css" rel="stylesheet" href="{{ url('js/image-uploader.min.css') }}">
    <style type="text/css">
        .product {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            border-bottom: 1px solid #ccc;
        }

        .wrapper {
            position: relative;
            width: 400px;
            height: 200px;
            -moz-user-select: none;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-color: white;
            border: 1px solid #000;
            border-radius: 10px;
            color: #CCC;
            text-align: center;
        }

        .content-wrapper {
            margin-bottom: 20px;
            width: 400px;
            text-align: center;
            margin-top: 20px;
        }

        .signature-pad {
            position: absolute;
            left: 0;
            top: 0;
            width: 400px;
            height: 200px;
        }

        @media (max-width: 768px) {

            .wrapper {
                width: 100%;
            }

            .signature-pad {
                width: 100%;
            }
        }

        input[type="number"] {
            -webkit-appearance: textfield;
            -moz-appearance: textfield;
            appearance: textfield;
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }

        .number-input {
            border: 2px solid #ddd;
            display: inline-flex;
            width: 8rem;
            padding: 0;
        }

        .number-input,
        .number-input * {
            box-sizing: border-box;
        }

        .number-input button {
            outline: none;
            -webkit-appearance: none;
            background-color: transparent;
            border: none;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 3rem;
            cursor: pointer;
            margin: 0;
            position: relative;
        }

        .number-input button:before,
        .number-input button:after {
            display: inline-block;
            position: absolute;
            content: '';
            width: .5rem;
            height: 2px;
            background-color: #212121;
            transform: translate(-50%, -50%);
        }

        .number-input button.plus:after {
            transform: translate(-50%, -50%) rotate(90deg);
        }

        .number-input input[type=number] {
            font-family: sans-serif;
            /* max-width: 5rem; */
            padding: .5rem;
            border: solid #ddd;
            border-width: 0 2px;
            font-size: 1rem;
            width: 4rem;
            height: 3rem;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .btn-submit {
            display: inline-block !important;
        }

        #clear {
            margin-top: 5px;
            display: inline-block;
        }

        .video-capture-container {
            width: 400px;
            height: 200px;
            border: 1px solid #000;
            border-radius: 10px;
            background-color: white;
            margin-bottom: 5px;
        }

        .video-capture-container video {
            border-radius: 10px;
            max-width: 400px;
            max-height: 198px;
            height: 198px;
            width: auto;
        }

        .canvas-image-container {
            width: 400px;
            height: 200px;
            border: 1px solid #000;
            border-radius: 10px;
            background-color: white;
            margin-bottom: 5px;
        }

        .canvas-image-container canvas {
            border-radius: 10px;
            max-width: 400px;
            width: auto !important;
            max-height: 198px;
            height: 198px;
            width: auto;
        }

        .span-photo {
            color: #CCC;
            position: relative;
            top: 25px;
        }

        .camera-capture-buttons-wrapper {
            text-align: center;
            display: inline-flex;
        }

        .container-photo {
            text-align: center;
            width: 400px;
            height: 250px;
        }

        .switch-camera-icon {
            width: 60px;
            height: 30px;
            position: relative;
            display: block;
            color: white;
            left: 50%;
            margin-left: -70px;
        }

        .btn-capture {
            margin-left: 105px;
        }

        .text-red {
            color: red;
        }

        .fileUploadThumbnails>div {
            float: left;
            margin-right: 1em;
            position: relative;
            border: 1px solid black
        }

        .fileUploadThumbnails .progress {
            position: absolute;
            bottom: 0;
            right: 0;
            left: 0;
            height: 10%;
            opacity: .5
        }

        .fileUploadThumbnails .progress>div {
            height: 100%;
            background: #c00
        }
    </style>
@endsection
