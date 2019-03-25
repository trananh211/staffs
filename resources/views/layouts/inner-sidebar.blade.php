<div class="inner-sidebar">
    <span class="inner-sidebar-title">Order</span>
    <span class="info-item">Mới:<span class="new badge blue">
            {{ (isset($new_order))? $new_order: '1' }}</span></span>
    <div class="inner-sidebar-divider"></div>
    <span class="info-item">Đang làm: <span class="new badge orange">{{ (isset($working_order))? $working_order: '2' }}</span></span> </span>
    <div class="inner-sidebar-divider"></div>
    <span class="info-item">Đang Kiểm tra: <span class="new badge">{{ (isset($checking_order))? $checking_order: '3' }}</span></span> </span>
    <div class="inner-sidebar-divider"></div>
    <span class="info-item">Quá thời gian: <span class="new badge red">{{ (isset($late_order))? $late_order: '4' }}</span></span> </span>
    <div class="inner-sidebar-divider"></div>

    <span class="inner-sidebar-title">New Design</span>
    <span class="info-item">Mới:<span class="new badge blue">5</span></span>
    <div class="inner-sidebar-divider"></div>
    <span class="info-item">Đang làm: <span class="new badge orange">5</span></span> </span>
    <div class="inner-sidebar-divider"></div>
    <span class="info-item">Đang Kiểm tra: <span class="new badge">6</span></span> </span>
    <div class="inner-sidebar-divider"></div>
    <span class="info-item">Quá thời gian: <span class="new badge red">7</span></span> </span>
    <div class="inner-sidebar-divider"></div>
</div>
