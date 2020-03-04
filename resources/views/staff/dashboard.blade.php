@extends('master')
@section('content')
    Staff
    <div class="row no-m-t no-m-b">
        <div class="col s12 m12 l4">
            <div class="card stats-card lime lighten-4">
                <div class="card-content">
                    <div class="card-options">
                        <ul>
                            <li class="red-text"><span class="badge cyan lighten-1">working</span></li>
                        </ul>
                    </div>
                    <span class="card-title">Jobs</span>
                    <span class="stats-counter"><span class="counter">{{ $reports['working'] }}</span><small>Đang làm</small></span>
                </div>
                <div id="sparkline-bar"></div>
            </div>
        </div>
        <div class="col s12 m12 l4">
            <div class="card stats-card green lighten-4">
                <div class="card-content">
                    <div class="card-options">
                        <ul>
                            <li><a href="javascript:void(0)"><i class="material-icons">more_vert</i></a></li>
                        </ul>
                    </div>
                    <span class="card-title">Job Đã Làm Xong</span>
                    <span class="stats-counter"><span class="counter">{{ $reports['work_in_week'] }}</span><small>Tuần Này</small></span>
                </div>
                <div id="sparkline-line"></div>
            </div>
        </div>
        <div class="col s12 m12 l4">
            <div class="card stats-card teal lighten-3">
                <div class="card-content">
                    <span class="card-title">Job hoàn thành</span>
                    <span class="stats-counter"><span class="counter">{{ $reports['work_in_month'] }}</span><small>Tháng Này</small></span>
                    <div class="percent-info green-text"><i class="material-icons">trending_up</i></div>
                </div>
                <div class="progress stats-card-progress">
                    <div class="determinate" style="width: 70%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row no-m-t no-m-b">
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Danh sách Job bạn đã hoàn thành</span>
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Job</th>
                            <th>Status</th>
                            <th>Qc</th>
                            <th>Variation</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Job</th>
                            <th>Status</th>
                            <th>Qc</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        <?php $i=0; ?>
                        @if (sizeof($lst_jobs) > 0)
                            @foreach( $lst_jobs as $job)
                                <?php $i++; ?>
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>{{ $job->sku.'-PID-'.$job->id }}</td>
                                    <td>{!! statusJob($job->status, $job->redo, $job->reason) !!}</td>
                                    <td>{{ $job->qc_name }}</td>
                                    <td class="center">
                                        {!! compareTime($job->updated_at, date("Y-m-d H:i:s")) !!}
                                    </td>
                                    <td>
                                        <!-- Modal Trigger -->
                                        <a class="waves-effect waves-light btn blue lighten-2 modal-trigger" href="#modal{{ $job->id }}">
                                            Chi Tiết
                                        </a>
                                        <!-- Modal Structure -->
                                        <div id="modal{{ $job->id }}" class="modal">
                                            <div class="modal-content">
                                                <!-- Chi tiết-->
                                                <div class="row">
                                                    <?php
                                                    $details = $job;
                                                    $images = explode(',', $job->image);
                                                    $del = explode('-;-;-', $job->detail);
                                                    ?>
                                                    <div class="col s12 m12 l12">
                                                        <div class="card">
                                                            <div class="card-content">
                                                                <ul>
                                                                    <li>Bạn phải lưu file Mockup với tên : <b>{{ $details->sku.'-PID-'.$details->id }}
                                                                            _mockup </b></li>
                                                                    <li>Bạn phải lưu file Design với tên : <b>{{ $details->sku.'-PID-'.$details->id }}_1, _L, _Left, _Front, _Back, _B
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
                                                                    {!! html_entity_decode($details->reason) !!}
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
                                                <!-- End Chi tiết-->
                                            </div>
                                        </div>
                                        @if ($job->status == env('STATUS_WORKING_CHECK'))
                                            <a href="{{ url('redoing-job/'.$job->id) }}"
                                               class="waves-effect waves-light red lighten-2 btn" >
                                                Làm Lại
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                               <td colspan="5" class="center">Tới thời điểm hiện tại bạn chưa làm Job nào. Bạn lười vãi đái.</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
