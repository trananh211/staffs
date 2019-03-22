@extends('popup')
@section('content')
    <div class="row">
        <?php
        $details = $details[0];
        $images = explode(',', $details->image);
        $del = explode('-;-;-', $details->detail);
        ?>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <ul>
                        <li>Bạn phải lưu file Mockup với tên : <b>{{ $details->number.'-PID-'.$details->id }}
                                _mockup </b></li>
                        <li>Bạn phải lưu file Design với tên : <b>{{ $details->number.'-PID-'.$details->id }}_1, _2, _3
                                ... </b></li>
                        <li>Tên sản phẩm : {{ $details->name }}</li>
                        <li>Link gốc sản phẩm : {{ $details->permalink }}</li>
                        <li>Và sử dụng những yêu cầu dưới đây để làm file sản xuất.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col s6 m12 l6">
            <div class="card">
                <div class="card-content">
                    <p class="card-title">Image</p>
                    <div class="material-placeholder" style="">
                        @foreach($images as $image)
                            <img class="materialboxed responsive-img initialized" src="{{ $image }}" alt="" style="">
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col s16 m12 l6">
            @if ($details->redo == 1)
                <div class="card red lighten-1">
                    <div class="card-content">
                        <p class="card-title">Redo</p>
                            {{ $details->reason }}
                        </p>
                    </div>
                </div>
            @endif
            <div class="card">
                <div class="card-content">
                    <p class="card-title">Note</p>
                    <table class="responsive-table">
                        <thead>
                        <tr>
                            <th data-field="id">Title</th>
                            <th data-field="name">Value</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($del as $value)
                            <?php
                            $tmp = explode(' :', $value);
                            $title = $tmp[0];
                            $tmp2 = (isset($tmp[1]) ? $tmp[1] : '');
                            if (strlen($tmp2) > 0) {
                                $tmp2 = explode(",", $tmp2);
                            }
                            ?>
                            <tr>
                                <td><?php echo $tmp[0]; ?></td>
                                <td>
                                    @if (is_array($tmp2))
                                        @foreach ($tmp2 as $k => $val)
                                            <?php
                                            $k++;
                                            if (strpos($val, 'http') !== false) {
                                                echo "<div><a href='$val' target='_blank' download='$val'>
                                                            Image $k
                                                        </a></div>";
                                            } else {
                                                echo $val;
                                            }
                                            ?>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

