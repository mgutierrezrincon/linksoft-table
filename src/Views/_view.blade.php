@php
    $modelName = str_replace('App\\SoapDian\\Models\\', '', str_replace('App\\Models\\', '', $model));
    $model = new $model();
    $model::booted();
    $module = $model->getTable();
    $fields = $model::getFields();
    $columns = $model::getColumns();
    $isUpdate = true;
    if (!isset($modelRow)) {
        $modelRow = new $model();
        $isUpdate = false;
    }
    $fieldTypes = $model::getFieldTypes();
    $fieldWidths = $model::getFieldWidths();
    $crudInModal = $model::getCrudInModal();
    $withImage = $model::getWithImage();
    $withPhoto = $model::getWithPhoto();
@endphp
<div class="container-fluid">
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

            <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                <div class="row">
                    @if (View::exists('pages.forms.' . strtolower($modelName)))
                        @include('pages.forms.' . strtolower($modelName))
                    @else
                        @if (View::exists('pages.forms.' . strtolower($modelName) . '_pre'))
                            @include('pages.forms.' . strtolower($modelName) . '_pre')
                        @endif
                        @foreach ($fields as $field)
                            <div
                                class="{{ array_key_exists($field, $fieldWidths) ? 'form-group col-md-' . $fieldWidths[$field] : 'form-group mb-20 ' }}">
                                <label>{{ trans($module . '.fields.' . $field) }}</label>
                                @if (array_key_exists($field, $fieldTypes))
                                    @switch($fieldTypes[$field]['type'])
                                        @case('select')
                                            <select class="form-control select"
                                                placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                readonly required name="{{ $field }}"
                                                id="{{ $field }}">
                                                @foreach ($fieldTypes[$field]['data'] as $key => $value)
                                                    <option id="{{ $field }}_{{ $key }}"
                                                        value="{{ $key }}"
                                                        @if ($key == $modelRow->{$field}) @selected(true) @endif>
                                                        {{ $value }}</option>
                                                @endforeach
                                            </select>
                                        @break

                                        @case('boolean')
                                            <select class="form-control select"
                                                placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                readonly required name="{{ $field }}"
                                                id="{{ $field }}">
                                                <option id="{{ $field }}_true" value="1"
                                                    @if ($modelRow->{$field} == 1) @selected(true) @endif>Si
                                                </option>
                                                <option id="{{ $field }}_false" value="0"
                                                    @if ($modelRow->{$field} == 0) @selected(true) @endif>No
                                                </option>
                                            </select>
                                        @break

                                        @case('textarea')
                                            <textarea class="form-control text-editor" placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                readonly required name="{{ $field }}" id="{{ $field }}">{{ $modelRow->{$field} }}</textarea>
                                        @break

                                        @default
                                            <input type="{{ Table::getInputType($field, $model) }}" class="form-control"
                                                placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                readonly
                                                @if ($field != 'from_date') @required(true) @endif
                                                name="{{ $field }}" id="{{ $field }}"
                                                value="{{ $modelRow->{$field} }}">
                                    @endswitch
                                @else
                                    <input type="{{ Table::getInputType($field, $model) }}" class="form-control"
                                        placeholder="{{ trans($module . '.fields.' . $field) }}"
                                        readonly
                                        @if ($field != 'password') required @endif name="{{ $field }}"
                                        id="{{ $field }}" value="{{ $modelRow->{$field} }}">
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
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
                </div>
            </div>
        </div>
    @endif
</div>
@section('javascript')
    <script>
        $(document).ready(function() {

        });
    </script>
@endsection

@section('css')
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
    </style>
@endsection
