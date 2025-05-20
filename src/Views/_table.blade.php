<?php
$modelName = str_replace('App\\SoapDian\\Models\\', '', str_replace('App\\Models\\', '', $model));
$model = new $model();
$model::booted();
$fields = $model::getFields();
$columns = $model::getColumns();
$rows = $model::basicTableFilter();
$modelNameAlt = $model::getModelNameAlt();
$module = $model->getTable();
$hideAddButton = $model::getHideAddButton();
$hideEditButton = $model::getHideEditButton();
$hideDeleteButton = $model::getHideDeleteButton();
$hideViewButton = $model::getHideViewButton();
$fieldTypes = $model::getFieldTypes();
$fieldWidths = $model::getFieldWidths();
$withImage = $model::getWithImage();
$crudInModal = $model::getCrudInModal();
?><div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (View::exists('pages.forms.' . strtolower($modelName) . '_pre_table'))
                @include('pages.forms.' . strtolower($modelName) . '_pre_table')
            @elseif (View::exists('pages.forms.' . strtolower($modelNameAlt) . '_pre_table'))
                @include('pages.forms.' . strtolower($modelNameAlt) . '_pre_table')
            @endif

            <div class="breadcrumb-main user-member justify-content-sm-between ">
                <div class=" d-flex justify-content-center breadcrumb-main__wrapper">
                    <div class="d-flex align-items-center user-member__title justify-content-center me-sm-25">
                        <h4 class="text-capitalize fw-500 breadcrumb-title">
                            {{ isset($titleTable) ? $titleTable : trans($module . '.table') }} </h4>
                    </div>

                    <form action="?" method="GET" class="d-flex align-items-center user-member__form my-sm-0 my-2">
                        <img src="{{ asset('assets/img/svg/search.svg') }}" alt="search" class="svg">
                        <input class="form-control me-sm-2 border-0 box-shadow-none" type="search"
                            placeholder="{{ trans('pagination.search') }}" aria-label="Search" name="s"
                            value="{{ Request::get('s') }}">
                    </form>

                </div>
                <div class="action-btn btn-group">
                    @if (View::exists('pages.' . strtolower($modelNameAlt) . '.buttons'))
                        @include('pages.' . strtolower($modelNameAlt) . '.buttons')
                    @endif

                    @if ($crudInModal)

                        @if (!$hideAddButton)
                            <a href="#" class="btn px-15 btn-primary" onclick="create()" data-bs-toggle="modal"
                                data-bs-target="#new-member">
                                <i class="las la-plus fs-16"></i>{{ trans($module . '.add') }}</a>
                        @endif

                        <!-- Modal -->
                        <div class="modal fade new-member" id="new-member" role="dialog"
                            aria-labelledby="staticBackdropLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-big">
                                <div class="modal-content radius-xl modal-content-big">
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-500 modal-title" id="staticBackdropLabel">
                                            {{ trans($module . '.add') }}</h6>
                                        <button type="button" class="close" data-bs-dismiss="modal"
                                            aria-label="Close">
                                            <img src="{{ asset('assets/img/svg/x.svg') }}" alt="x"
                                                class="svg">
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="new-member-modal">
                                            <form id="form-modal" action="{{ url(Request::path()) }}" method="POST">
                                                <div class="form-group">
                                                    <div class="row">
                                                        <input type="hidden" name="_method" value="POST"
                                                            id="method">
                                                        {{ csrf_field() }}
                                                        <input type="hidden" name="return_url"
                                                            value="{{ url()->full() }}">
                                                        @if (View::exists('pages.forms.' . strtolower($modelName)))
                                                            @include('pages.forms.' . strtolower($modelName))
                                                        @else
                                                            @if (View::exists('pages.forms.' . strtolower($modelName) . '_pre'))
                                                                @include(
                                                                    'pages.forms.' .
                                                                        strtolower($modelName) .
                                                                        '_pre')
                                                            @endif
                                                            @foreach ($fields as $field)
                                                                <div
                                                                    class="{{ array_key_exists($field, $fieldWidths) ? 'col-md-' . $fieldWidths[$field] : 'mb-20 ' }}">
                                                                    <label>{{ trans($module . '.fields.' . $field) }}</label>
                                                                    @if (array_key_exists($field, $fieldTypes))
                                                                        @switch($fieldTypes[$field]['type'])
                                                                            @case('select')
                                                                                <select class="form-control select"
                                                                                    placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                                                    {{ Table::getRequired($field, $model) }}
                                                                                    {{ Table::getReadOnly($field, $model) }}
                                                                                    name="{{ $field }}"
                                                                                    id="{{ $field }}">
                                                                                    @foreach ($fieldTypes[$field]['data'] as $key => $value)
                                                                                        <option
                                                                                            id="{{ $field }}_{{ $key }}"
                                                                                            value="{{ $key }}">
                                                                                            {{ $value }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            @break

                                                                            @case('multiselect')
                                                                                <select class="form-control select foreign"
                                                                                    multiple
                                                                                    placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                                                    {{ Table::getRequired($field, $model) }}
                                                                                    {{ Table::getReadOnly($field, $model) }}
                                                                                    name="{{ $field }}[]"
                                                                                    id="{{ $field }}">
                                                                                </select>
                                                                            @break

                                                                            @case('foreign')
                                                                                <select class="form-control select foreign"
                                                                                    placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                                                    {{ Table::getRequired($field, $model) }}
                                                                                    {{ Table::getReadOnly($field, $model) }}
                                                                                    name="{{ $field }}"
                                                                                    id="{{ $field }}">

                                                                                </select>
                                                                            @break

                                                                            @case('boolean')
                                                                                <select class="form-control select"
                                                                                    placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                                                    {{ Table::getRequired($field, $model) }}
                                                                                    {{ Table::getReadOnly($field, $model) }}
                                                                                    name="{{ $field }}"
                                                                                    id="{{ $field }}">
                                                                                    <option id="{{ $field }}_true"
                                                                                        value="1">Si</option>
                                                                                    <option id="{{ $field }}_false"
                                                                                        value="0">No</option>
                                                                                </select>
                                                                            @break

                                                                            @case('textarea')
                                                                                <textarea class="form-control text-editor" placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                                                    {{ Table::getReadOnly($field, $model) }} {{ Table::getRequired($field, $model) }} name="{{ $field }}"
                                                                                    id="{{ $field }}"></textarea>
                                                                            @break

                                                                            @default
                                                                                <input
                                                                                    type="{{ Table::getInputType($field, $model) }}"
                                                                                    class="form-control {{ Table::getInputClass($field, $model) }}"
                                                                                    placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                                                    {{ Table::getReadOnly($field, $model) }}
                                                                                    {{ Table::getRequired($field, $model) }}
                                                                                    name="{{ $field }}"
                                                                                    id="{{ $field }}">
                                                                        @endswitch
                                                                    @else
                                                                        <input
                                                                            type="{{ Table::getInputType($field, $model) }}"
                                                                            class="form-control"
                                                                            placeholder="{{ trans($module . '.fields.' . $field) }}"
                                                                            {{ Table::getReadOnly($field, $model) }}
                                                                            {{ Table::getRequired($field, $model) }}
                                                                            name="{{ $field }}"
                                                                            id="{{ $field }}">
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                                @if (View::exists('pages.forms.' . strtolower($modelName) . '_post'))
                                                    @include('pages.forms.' . strtolower($modelName) . '_post')
                                                @endif
                                                <div class="button-group d-flex pt-25" id="modal-buttons">
                                                    <button
                                                        class="btn btn-primary btn-default btn-squared text-capitalize"
                                                        id="modal-btn-submit">{{ trans($module . '.add') }}
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-light btn-default btn-squared fw-400 text-capitalize b-light color-light"
                                                        data-bs-dismiss="modal">{{ trans('general.cancel') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal -->
                    @else
                        @if (!$hideAddButton)
                            <a href="{{ url(Request::path()) }}/create" class="btn px-15 btn-primary">
                                <i class="las la-plus fs-16"></i>{{ trans($module . '.add') }}</a>
                        @endif
                    @endif

                </div>
            </div>

        </div>
    </div>
    @if (session('success'))
        <div class=" alert alert-success  alert-dismissible fade show " role="alert">
            <div class="alert-content">
                <p>{{ session('success') }}</p>
                <button type="button" class="btn-close text-capitalize" data-bs-dismiss="alert" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="svg replaced-svg">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    @endif
    @if (session('fail'))
        <div class=" alert alert-danger  alert-dismissible fade show " role="alert">
            <div class="alert-content">
                <p>{{ session('fail') }}</p>
                <button type="button" class="btn-close text-capitalize" data-bs-dismiss="alert" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="svg replaced-svg">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-12">
            <div class="userDatatable global-shadow border-light-0 p-30 bg-white radius-xl w-100 mb-30">
                <div class="table-responsive">
                    <table class="table mb-0 table-borderless table-hover table-striped table-responsive">
                        <thead>
                            <tr class="userDatatable-header">
                                <th class="static text-center" scope="col">
                                    <span class="userDatatable-title">{{ trans('tables.actions') }}</span>
                                </th>
                                @foreach ($columns as $field)
                                    <th scope="col">
                                        <span
                                            class="userDatatable-title">{{ trans($module . '.fields.' . $field) }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="static">
                                        <ul class="orderDatatable_actions mb-0 d-flex">
                                            @if (!$hideViewButton)
                                                @if ($crudInModal)
                                                    <li>
                                                        <a href="#" class="view"
                                                            onclick="view({{ json_encode($row->attributesToArray()) }})"
                                                            data-bs-toggle="modal" data-bs-target="#new-member">
                                                            <i class="uil uil-eye"></i>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li>
                                                        <a href="{{ url(Request::path()) }}/{{ $row->id }}"
                                                            class="view">
                                                            <i class="uil uil-eye"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endif
                                            @if (View::exists('pages.table.buttons.' . strtolower($modelName)))
                                                @include('pages.table.buttons.' . strtolower($modelName))
                                            @elseif(View::exists('pages.table.buttons.' . strtolower($modelNameAlt)))
                                                @include('pages.table.buttons.' . strtolower($modelNameAlt))
                                            @endif
                                            @if (!$hideEditButton)
                                                <li>
                                                    @if ($crudInModal)
                                                        <a href="#" class="edit"
                                                            onclick="edit({{ json_encode($row->attributesToArray()) }})"
                                                            data-bs-toggle="modal" data-bs-target="#new-member">
                                                            <i class="uil uil-edit"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ url(Request::path()) }}/{{ $row->id }}/edit"
                                                            class="edit">
                                                            <i class="uil uil-edit"></i>
                                                        </a>
                                                    @endif
                                                </li>
                                            @endif
                                            @if (!$hideDeleteButton)
                                                @if (auth()->user()?->type == 'super_admin' || auth()->user()?->type == 'admin')
                                                    <li>
                                                        <a href="#" data-bs-toggle="modal"
                                                            data-bs-target="#modal-info-delete-{{ $row->id }}"
                                                            class="remove" onclick="delete({{ $row->id }})">
                                                            <i class="uil uil-trash-alt"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endif
                                        </ul>
                                    </td>
                                    @foreach ($columns as $i => $field)
                                        <td @if ($i == 0) scope="row" @endif
                                            data-label="{{ trans($module . '.fields.' . $field) }}"
                                            align="{{ Table::getAlign($field, $model) }}">
                                            <div class="userDatatable-content">
                                                @if (array_key_exists($field, $fieldTypes))
                                                    @switch($fieldTypes[$field]['type'])
                                                        @case('boolean')
                                                            {!! $row->attributesToArray()[$field] == 1 ? 'Si' : 'No' !!}
                                                        @break

                                                        @case('select')
                                                            @if (array_key_exists($row->attributesToArray()[$field], $fieldTypes[$field]['options']))
                                                                {!! $fieldTypes[$field]['options'][$row->attributesToArray()[$field]] !!}
                                                            @else
                                                                {{ $row->attributesToArray()[$field] }}
                                                            @endif
                                                        @break

                                                        @case('price')
                                                            ${!! number_format($row->attributesToArray()[$field], 0) !!}
                                                        @break

                                                        @case('foreign')
                                                            @if (array_key_exists('multiple', $fieldTypes[$field]))
                                                            @else
                                                                <?php try{ ?>
                                                                {!! $row->{Str::camel(str_replace('_id', '', $field))}()->withoutGlobalScopes()->first()
                                                                    ?->{$fieldTypes[$field]['foreign_field']} !!}

                                                                <?php }catch(Error $e){ echo $row->{Str::camel(str_replace('_id','',$field))}()->getRelated()?->{$fieldTypes[$field]['foreign_field']}; }?>
                                                            @endif
                                                        @break

                                                        @case('date')
                                                            {!! $row->{$field}?->format('d/m/Y') !!}
                                                        @break

                                                        @case('datetime')
                                                            @if (!empty($row->{$field}))
                                                                {{ \Carbon\Carbon::parse($row->attributesToArray()[$field], 'America/Bogota')?->format('Y-m-d H:i') }}
                                                            @endif
                                                            {{-- {{ dd($row->attributesToArray()[$field],\Carbon\Carbon::parse($row->attributesToArray()[$field],'America/Bogota')) }} --}}
                                                        @break

                                                        @default
                                                            @if (array_key_exists($field, $model->getCasts()))
                                                                @switch($model->getCasts()[$field])
                                                                    @case('datetime')
                                                                        {{ \Carbon\Carbon::parse($row->attributesToArray()[$field], 'America/Bogota')?->format('Y-m-d H:i') }}
                                                                    @break

                                                                    @case('date')
                                                                        {{ \Carbon\Carbon::parse($row->attributesToArray()[$field], 'America/Bogota')?->format('Y-m-d') }}
                                                                    @break

                                                                    @default
                                                                        {{ $row->attributesToArray()[$field] }}
                                                                @endswitch
                                                            @else
                                                                {{ $row->attributesToArray()[$field] }}
                                                            @endif
                                                        @endswitch
                                                    @else
                                                        {!! $row->attributesToArray()[$field] !!}
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end pt-30">
                        {{ $rows->links('vendor.pagination.default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @foreach ($rows as $row)
        @if (
            !$hideDeleteButton ||
                in_array(strtolower($modelName), [
                    'order',
                    'stockadjustment',
                    'stock_adjustment',
                    'accountingnote',
                    'incomereceipt',
                    'income_receipt',
                    'orderincomereceipt',
                    'order_income_receipt',
                    'outcomereceipt',
                    'outcome_receipt',
                    'remissionoutcomereceipt',
                    'remission_outcome_receipt',
                    'supplierdevolution',
                    'supplier_devolution',
                    'supplierremission',
                    'supplier_remission',
                    'buy',
                ]))
            @include('pages.partials._modal-delete')
        @endif

        @if (View::exists('pages.table.modals.' . strtolower($modelName)))
            @include('pages.table.modals.' . strtolower($modelName))
        @endif
    @endforeach
    @section('javascript')
        <script>
            @if (request()->get('sale') > 0)

                var iframe = document.createElement('iframe');
                document.body.appendChild(iframe);
                iframe.style.display = 'none';
                iframe.onload = function() {
                    setTimeout(function() {
                        iframe.focus();
                        iframe.contentWindow.print();
                    }, 1000);
                };
                iframe.src = "{!! url('es/sales/' . request()->get('sale') . '/report') !!}";
            @endif
            let attributes = {!! json_encode(
                array_values(array_unique(array_values(array_merge(array_values($columns), array_values($fields))))),
            ) !!};
            @if ($crudInModal && !$hideAddButton)
                function create() {

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
                    $("#form-modal").attr("action", "{{ url(Request::path()) }}");
                    $("#form-modal").attr("method", "POST");
                    $("#method").val("POST");
                    $(".modal-title").html("{{ trans($module . '.add') }}");
                    $("#modal-btn-submit").text("{{ trans($module . '.add') }}");
                    $("#modal-buttons").removeClass('hidden');
                    attributes.forEach(function(item, i) {
                        if ($("#" + item).hasClass("select")) {
                            $("#" + item + ">option").removeAttr('selected');
                        } else {
                            if ($("#" + item).hasClass("text-editor")) {
                                $(".text-editor").trumbowyg('html', '');
                            } else {
                                $("#" + item).val("");
                            }
                        }
                    });
                }
            @endif
            let formEdit = false;
            @if ($crudInModal && !$hideEditButton)
                function edit(values) {
                    formEdit = true;
                    $("#form-modal").attr("action", "{{ url(Request::path()) }}/" + values["id"]);
                    $("#form-modal").attr("method", "POST");
                    $("#method").val("PATCH");
                    $(".modal-title").html("{{ trans($module . '.edit') }}");
                    $("#modal-btn-submit").text("{{ trans($module . '.edit') }}");
                    $("#modal-buttons").removeClass('hidden');
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
                                                $("#" + item).append("<option value='" + json['id'] + "'>" +
                                                    json['name'] +
                                                    "</option>");
                                                $("#" + item).val(json['id']).trigger('change');
                                            });
                                        }
                                    @elseif ($fieldTypes[$field]['type'] == 'multiselect')
                                        if (item == '{{ $field }}') {
                                            $("#{{ $field }}").off().html('').select2({
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
                                                        $("#" + item).append("<option value='" + json[
                                                                'id'] + "'>" +
                                                            json['name'] +
                                                            "</option>");
                                                        $("#" + item).val(values[item]).trigger('change');
                                                    });
                                            });
                                        }
                                    @endif
                                @endif
                            @endforeach
                        } else if ($("#" + item).hasClass("select")) {
                            $("#" + item + ">option").removeAttr('selected');
                            $("#" + item + "_" + values[item]).attr('selected', 'selected');
                        } else {
                            if ($("#" + item).hasClass("text-editor")) {
                                $(".text-editor").trumbowyg('html', values[item]);
                            } else if (item == 'from_date' || item == 'to_date') {
                                $("#" + item).val(values[item].substr(0, 10));
                            } else {
                                $("#" + item).val(values[item]);
                            }
                        }
                    });
                }
            @endif
            function view(values) {
                $("#form-modal").attr("action", "#");
                $("#form-modal").attr("method", "GET");
                $("#method").val("GET");
                $(".modal-title").html("{{ trans($module . '.view') }}");
                $("#modal-btn-submit").text("{{ trans($module . '.view') }}");
                $("#modal-buttons").addClass('hidden');
                attributes.forEach(function(item, i) {
                    if ($("#" + item).hasClass("foreign")) {
                        @foreach ($fields as $field)
                            @if (array_key_exists($field, $fieldTypes))
                                @if ($fieldTypes[$field]['type'] == 'foreign')
                                    if (item == '{{ $field }}') {
                                        $("#{{ $field }}").off().select2({
                                            minimumResultsForSearch: 0,
                                            placeholder: "Selecciona un producto",
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
                                            $("#" + item).append("<option value='" + json['id'] + "'>" +
                                                json['name'] +
                                                "</option>");
                                            $("#" + item).val(json['id']).trigger('change');
                                        });
                                    }
                                @elseif ($fieldTypes[$field]['type'] == 'multiselect')
                                    if (item == '{{ $field }}') {
                                        $("#{{ $field }}").off().html('').select2({
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
                                                    $("#" + item).append("<option value='" + json[
                                                            'id'] + "'>" +
                                                        json['name'] +
                                                        "</option>");
                                                    $("#" + item).val(values[item]).trigger('change');
                                                });
                                        });
                                    }
                                @endif
                            @endif
                        @endforeach
                    } else if ($("#" + item).hasClass("select")) {
                        $("#" + item + ">option").removeAttr('selected');
                        $("#" + item + "_" + values[item]).attr('selected', 'selected');
                    } else {
                        if ($("#" + item).hasClass("text-editor")) {
                            $(".text-editor").trumbowyg('html', values[item]);
                        } else if (item == 'from_date' || item == 'to_date') {
                            $("#" + item).val(values[item].substr(0, 10));
                        } else {
                            $("#" + item).val(values[item]);
                        }
                    }
                });
            }
        </script>
    @endsection
    @section('css')
        <style type="text/css">
            .userDatatable table tbody tr td {
                padding-top: 4px;
                padding-bottom: 4px;
            }

            .table-striped>tbody>tr>td.static {
                padding: 4px !important;
                text-align: center;
                text-align: -webkit-center;
            }

            @media (min-width: 768px) {
                .static {
                    max-width: 120px;
                    position: sticky;
                    left: 0;
                }

                .table-striped>tbody>tr:nth-of-type(odd)>td.static {
                    background-color: #dcdcdd;
                    color: var(--bs-table-striped-color);
                }

                .table-striped>tbody>tr:nth-of-type(even)>td.static {
                    background-color: #dcdcdd;
                    color: var(--bs-table-striped-color);
                }
            }
        </style>
    @endsection
