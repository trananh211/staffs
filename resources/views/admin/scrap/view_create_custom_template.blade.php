@extends('master')
@section('content')
    <div class="row">
        <?php
            if ($check_done) {
                $url = 'make-custom-template';
                $page_title = 'Create Custom Template';
                $button_submit = 'Create Template';
                $class_background = '#e8f5e9 green lighten-5';
            } else {
                $url = 'check-custom-template';
                $page_title = 'Check Custom Template';
                $button_submit = 'Check Template';
                $class_background = '#f9fbe7 lime lighten-5';
            }

            if (isset($rq)) {
                $r = true;
            } else {
                $r = false;
            }
        ?>
        <div class="col s12">
            <div class="page-title">{{ $page_title }}</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content {{ $class_background }}">
                    <form class="p-v-xs" action="{{url($url)}}" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                        <div class="row">
                            <div class="input-field col s4">
                                <select id="woo-tem-choose-store" name="store_id">
                                    <option value="" disabled selected>Choose your option</option>
                                    @foreach($stores as $store)
                                        <option
                                            {{ ($r && ($store->id == $rq['store_id']))? 'selected' : '' }}
                                            value="{{ $store->id }}" con_key="{{ $store->consumer_key }}"
                                                con_sec="{{ $store->consumer_secret }}" url="{{ $store->url }}">
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label>Store Select</label>
                            </div>
                            <div class="input-field col s3">
                                <input name="id_product" placeholder="Nhập mã ID của product mẫu ở đây" type="text" class="validate"
                                       value="{{ ($r)? $rq['id_product'] : '' }}" required>
                                <label class="active"> <span class="red-text">*</span>  Id Product Template</label>
                            </div>
                            <div class="input-field col s5">
                                <input name="web_link" value="{{ ($r)? $rq['web_link'] : '' }}" placeholder="https://f4tool.xyz.test/catalog/search=vidu" type="text" class="validate" required>
                                <label class="active">
                                    <span class="red-text">*</span> Url
                                    <small class="blue-text text-darken-1">Nhập url của website catalogs cần crawler vào đây</small>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s5">
                                <input name="title_catalog_class" value="{{ ($r)? $rq['title_catalog_class'] : '' }}" placeholder="section.site-content div.container div.col-md-3" type="text" class="validate" required>
                                <label class="active">
                                    <span class="red-text">*</span> Catalog Class
                                    <small class="blue-text text-darken-1">Nhập class của product trong list catalog vào đây</small>
                                </label>
                            </div>
                            <div class="input-field col s3">
                                <input name="title_product_class" value="{{ ($r)? $rq['title_product_class'] : '' }}" placeholder="a .grid-view-item__title" type="text" class="validate" required>
                                <label class="active">
                                    <span class="red-text">*</span> Title Product Class
                                    <small class="blue-text text-darken-1">Nhập class của product để lấy title</small>
                                </label>
                            </div>
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['domain_origin'] : '' }}" name="domain_origin" placeholder="https://republicandogs.com" type="text" class="validate">
                                <label class="active">
                                    <span class="red-text">*</span> Domain Origin
                                    <small class="blue-text text-darken-1">Nếu link của catalog không chứa domain thì bắt buộc khai báo</small>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['page_catalog_class'] : '' }}" name="page_catalog_class" placeholder="ul.pager li:nth-last-child(1) a" type="text" class="validate" >
                                <label class="active">Page Class
                                    <small class="blue-text text-darken-1">Nhập class của page website cần crawler vào đây</small>
                                </label>
                            </div>
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['last_page_catalog_class'] : '' }}" name="last_page_catalog_class" placeholder="ul.pager li:nth-last-child(1) .disabled" type="text" class="validate" >
                                <label class="active">
                                    Last Page Class
                                    <small class="blue-text text-darken-1">Nhập class page nhận dạng trang cuối cùng</small><br>
                                    <small class="red-text text-darken-1">Nếu không có class nhận dạng thì bỏ trống, tool sẽ lấy page class ở trên để so sánh</small>
                                </label>
                            </div>
                            <div class="input-field col s2">
                                <input value="{{ ($r)? $rq['page_string'] : '' }}" name="page_string" placeholder="&page=, ?pages=, %p=..." type="text" class="validate" >
                                <label class="active">
                                    Page String
                                </label>
                            </div>
                            <div class="input-field col s1">
                                <input value="{{ ($r)? $rq['last_page_catalog_number'] : '' }}" name="last_page_catalog_number" placeholder="10" type="text" class="validate" >
                                <label class="active">
                                    Last Number Page
                                </label>
                            </div>
                            <div class="input-field col s1">
                                <input value="{{ ($r)? $rq['page_exclude_string'] : '' }}" name="page_exclude_string" placeholder="Nên điền nếu trong link page có xuất hiện ký tự số" type="text" class="validate" >
                                <label class="active">
                                    Page Exclude Text
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s7">
                                <input value="{{ ($r)? $rq['product_page_title_class'] : '' }}" required name="product_page_title_class" type="text" class="validate" placeholder="h1.title-product, ...">
                                <label class="active">
                                    <span class="red-text">*</span> Product Page Title Class
                                    <small class="blue-text text-darken-1">Chọn title nhận dạng của product trong product page</small>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s5">
                                <input value="{{ ($r)? $rq['image_class'] : '' }}" name="image_class" type="text" class="validate" required placeholder="li.product-single__thumbnails-item a.product-single__thumbnail">
                                <label class="active"><span class="red-text">*</span> List Image Class</label>
                            </div>
                            <div class="input-field col s2">
                                <input value="{{ ($r)? $rq['element_link'] : '' }}" name="element_link" type="text" class="validate" required placeholder="a, span.link-thumb, div.link...">
                                <label class="active"><span class="red-text">*</span> Element link</label>
                            </div>
                            <div class="input-field col s2">
                                <input value="{{ ($r)? $rq['attr_link'] : '' }}" name="attr_link" type="text" class="validate" required placeholder="href, src, thumb...">
                                <label class="active"><span class="red-text">*</span> Attr Link</label>
                            </div>
                            <div class="input-field col s3">
                                <input value="{{ ($r)? $rq['http_image'] : '' }}" name="http_image" type="text" class="validate" placeholder="https:, http:, ...">
                                <label class="active">
                                    Http Image<br>
                                    <small class="blue-text text-darken-1">Chọn trường này nếu ảnh của sản phẩm không chứa http</small>
                                </label>
                            </div>
                        </div>

                        <div class="row"><hr class="mt-2 mb-3" style="border: 1px dotted #ccc"/></div>

                        <div class="row">
                            <div class="input-field col s2">
                                <input value="{{ ($r)? $rq['product_tag'] : '' }}" name="product_tag" type="text" class="validate" placeholder="mug, canvas, shirt ..">
                                <label class="active">
                                    Product Tag<br>
                                    <small class="blue-text text-darken-1">Chọn khi không phải name, age</small>
                                </label>
                            </div>
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['auto_sku'] : '' }}" value="" name="auto_sku" type="text" class="validate" placeholder="Điền mã sku của sản phẩm">
                                <label for="active">Auto SKU
                                    <small class="blue-text text-darken-1">Nếu sản phẩm không có SKU cố định. Hãy chọn trường này để hệ thống gen tự động mã SKU</small>
                                </label>
                            </div>
                            <div class="input-field col s2">
                                <select name="template_tool_status">
                                    <option value="" disabled selected>Choose your option</option>
                                    @foreach ($template_tool_status as $tool_status => $value_status)
                                        <option value="{{ $tool_status }}">{{ $value_status }}</option>
                                    @endforeach
                                </select>
                                <label>Title Status Select</label>
                            </div>
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['text_exclude'] : '' }}" name="text_exclude" type="text" class="validate"
                                       placeholder="H8L1AF01 hoặc BL750">
                                <label class="active">
                                    Exclude Text<br>
                                    <small class="blue-text text-darken-1">Nếu tiêu đề sản phẩm có chứa ký tự số bắt buộc phải chọn trường này</small>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['image_choose'] : '' }}" name="image_choose" type="text" class="validate" placeholder="1,2,3,4,5">
                                <label class="active">
                                    Image Choose<br>
                                    <small class="blue-text text-darken-1">Chọn số thứ tự ảnh muốn thêm vào store. Phân cách bởi dấu , </small>
                                </label>
                            </div>

                            <div class="input-field col s2">
                                <label class="active">
                                    Từ khóa thêm tự động<span class="red-text text-darken-1">(*)</span><br>
                                    <small class="blue-text text-darken-1">Thêm từ khóa vào tiêu đề sản phẩm</small>
                                </label>
                                <div class="switch m-b-md">
                                    <label>
                                        Off
                                        <input type="checkbox" name="keyword_import">
                                        <span class="lever"></span>
                                        On
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s12">
                                <input value="{{ ($r)? $rq['exclude_image'] : '' }}" name="exclude_image" type="text" class="validate" placeholder="Thêm link image exclude">
                                <label class="active">
                                    Điền link ảnh không muốn thêm vào website. Esty dùng thuộc tính : data-src-zoom-image <br>
                                    <small class="blue-text text-darken-1">Dán link ảnh phân cách với nhau bằng dấu ,</small>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['url'] : '' }}" name="url" type="text" class="validate js_url" required>
                                <label class="active">Url</label>
                            </div>
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['consumer_key'] : '' }}" name="consumer_key" type="text" class="validate js_con_key" required>
                                <label class="active">consumer_key</label>
                            </div>
                            <div class="input-field col s4">
                                <input value="{{ ($r)? $rq['consumer_secret'] : '' }}" name="consumer_secret" type="text" class="validate js_con_sec" required>
                                <label class="active">consumer_secret</label>
                            </div>
                        </div>
                        <div class="row">
                            <button type="submit" class="right waves-effect waves-light btn blue">
                                {{ $button_submit }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @if(isset($info))
        <div class="row">
            <div class="card">
                <div class="card-content">
                    <table class="responsive-table highlight">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center" data-field="name">Name</th>
                            <th class="center" data-field="id">Tag</th>
                            <th class="center" data-field="name">Image</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(sizeof($info) > 0)
                            <?php $i = 1; ?>
                            @foreach($info as $key => $list)
                                <tr>
                                    <td class="center">{{ $i++ }}</td>
                                    <td class="center">
                                        <a href="{{ $list['link'] }}" target="_blank">{{ $list['name'] }} {{ $list['sku'] }}</a>
                                    </td>
                                    <td class="center"> {{ $list['tag_name'] }}</td>
                                    <td class="center">
                                        @if($key == 0)
                                            @foreach($list['image'] as $img)
                                                {!! thumb_w($img, '50', 'img'.$i) !!}
                                            @endforeach
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" class="center">
                                    Không tồn tại sản phẩm nào cả
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
