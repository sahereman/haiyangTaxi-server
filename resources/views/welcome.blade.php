{{--<!DOCTYPE html>--}}
{{--<html>--}}
{{--<head>--}}
    {{--<meta charset="UTF-8">--}}
    {{--<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">--}}
    {{--<meta name="renderer" content="webkit">--}}
    {{--<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">--}}
    {{--<meta name="keywords" Content="{{\App\Models\Config::config('keywords')}}">--}}
    {{--<meta name="description" content="{{\App\Models\Config::config('description')}}"/>--}}
    {{--<meta name="csrf-token" content="{{ csrf_token() }}">--}}
    {{--<title>{{\App\Models\Config::config('title')}}</title>--}}
    {{--<link rel="stylesheet" type="text/css" href="/css/welcome.css"/>--}}
{{--</head>--}}
{{--<body>--}}
{{--<div class="content">--}}
    {{--<div class="content_left">--}}
        {{--<!--pc展示-->--}}
        {{--<img class="img_pc" src="/img/pc_1.png">--}}
        {{--<!--mobile展示-->--}}
        {{--<img class="img_mobile" src="/img/mobile_phone.png">--}}
    {{--</div>--}}
    {{--<div class="content_right">--}}
        {{--<div class="right_title">--}}
            {{--<h3>海阳市出租车</h3>--}}
            {{--<p>出租车移动出行平台，出行共享</p>--}}
        {{--</div>--}}
        {{--<ul>--}}
            {{--<li>--}}
                {{--<div class="small_title_icon">--}}
                    {{--<a class="for_download" href="javascript:void(0)" id="small_title_btn">--}}
                        {{--<img src="/img/call.png">--}}
                    {{--</a>--}}
                {{--</div>--}}
                {{--<div class="small_title">--}}
                    {{--<h4>一键呼叫出租车</h4>--}}
                    {{--<p>无需路边等待出租车，手机即可一键呼叫出租接驾，方便快捷</p>--}}
                {{--</div>--}}
            {{--</li>--}}
            {{--<li>--}}
                {{--<div class="small_title_icon">--}}
                    {{--<a class="for_download" href="javascript:void(0)">--}}
                        {{--<img src="/img/location.png">--}}
                    {{--</a>--}}
                {{--</div>--}}
                {{--<div class="small_title">--}}
                    {{--<h4>智能定位系统</h4>--}}
                    {{--<p>自动定位，智能常用和历史地点推荐，让您省心省力</p>--}}
                {{--</div>--}}
            {{--</li>--}}
            {{--<li>--}}
                {{--<div class="small_title_icon">--}}
                    {{--<a class="for_download" href="javascript:void(0)">--}}
                        {{--<img src="/img/road.png">--}}
                    {{--</a>--}}
                {{--</div>--}}
                {{--<div class="small_title">--}}
                    {{--<h4>行程一目了然</h4>--}}
                    {{--<p>记录您的每一条行程记录，您的安全保障</p>--}}
                {{--</div>--}}
            {{--</li>--}}
        {{--</ul>--}}
        {{--<div class="small_program_code">--}}
            {{--<img src="{{\App\Models\Config::config('client_qrcode')}}">--}}
            {{--<p>扫一扫，出行新体验</p>--}}
        {{--</div>--}}
    {{--</div>--}}
{{--</div>--}}
{{--</body>--}}

{{--<script type="text/javascript">--}}
    {{--if(/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {--}}
        {{--document.getElementById("small_title_btn").addEventListener('touchend', touch, false);--}}
    {{--}else {--}}
        {{--document.getElementById("small_title_btn").addEventListener('click', touch, false);--}}
    {{--}--}}

    {{--var tn = 0;--}}
    {{--function touch(event) {--}}
        {{--var event = event || window.event;--}}
        {{--switch (event.type) {--}}
            {{--case "touchstart":--}}
                {{--break;--}}
            {{--case "touchend":--}}
                {{--tn++;--}}
                {{--if (tn >= 10) { //大于点击次数开始下载--}}
                    {{--var src = '{{\App\Models\Config::config('driver_android_apk')}}';--}}
                    {{--var form = document.createElement('form');--}}
                    {{--form.action = src;--}}
                    {{--document.getElementsByTagName('body')[0].appendChild(form);--}}
                    {{--form.submit();--}}
                    {{--tn = 0;--}}
                {{--}--}}
                {{--break;--}}
            {{--case "click":--}}
                {{--tn++;--}}
                {{--if (tn >= 10) { //大于点击次数开始下载--}}
                    {{--var src = '{{\App\Models\Config::config('driver_android_apk')}}';--}}
                    {{--var form = document.createElement('form');--}}
                    {{--form.action = src;--}}
                    {{--document.getElementsByTagName('body')[0].appendChild(form);--}}
                    {{--form.submit();--}}
                    {{--tn = 0;--}}
                {{--}--}}
                {{--break;--}}
            {{--case "touchmove":--}}
                {{--// 执行滑动事件--}}
                {{--break;--}}
        {{--}--}}
    {{--}--}}
{{--</script>--}}
{{--</html>--}}
