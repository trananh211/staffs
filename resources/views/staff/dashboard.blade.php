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
@endsection
