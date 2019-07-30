<div class="inner-sidebar">
    @if( sizeof($data) > 0)
        <span class="inner-sidebar-title">Order</span>
        <span class="info-item">Mới:<span class="new badge blue">{{ (in_array('pub',$data))? $data['pub']['new'] : '-' }}</span></span>
        <div class="inner-sidebar-divider"></div>
        <span class="info-item">Đang làm: <span
                class="new badge orange">{{ (in_array('pub',$data))? $data['pub']['working'] : '-' }}</span></span> </span>
        <div class="inner-sidebar-divider"></div>
        <span class="info-item">Đang Kiểm tra: <span
                class="new badge">{{ (in_array('pub',$data))? $data['pub']['order_checking'] : '-' }}</span></span> </span>
        <div class="inner-sidebar-divider"></div>

        <span class="inner-sidebar-title">New Design</span>

        <span class="info-item">Đang làm: <span
                class="new badge orange">{{ (in_array('pub',$data))? $data['pub']['idea_new'] : '-'  }}</span></span> </span>
        <div class="inner-sidebar-divider"></div>
        <span class="info-item">Đang kiểm tra:<span
                class="new badge blue">{{ (in_array('pub',$data))? $data['pub']['idea_check'] : '-' }}</span></span>
        <div class="inner-sidebar-divider"></div>
    @endif
</div>
