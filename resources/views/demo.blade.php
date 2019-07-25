<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 上述3个meta标签*必须*放在最前面，任何其他内容都*必须*跟随其后！ -->
    <meta name="description" content="">
    <meta name="author" content="">
    <title>{{\App\Models\Config::config('title')}}</title>


    <!-- Bootstrap core CSS -->
    <link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<div class="container">
    <h1 style="text-align: center;">{{\App\Models\Config::config('title')}}</h1>
    <hr>

    @foreach(\App\Models\cat::all() as $cat)
        <div class="row">
            <div class="col-md-12">
                <h2>{{$cat->name}}</h2>
                @foreach($cat->imgs as $img)

                    <div class="col-md-3">
                        <div class="thumbnail">
                            <img src="{{$img->img_url}}" style="height: 200px;">
                            <div class="caption">
                                <h4>{{$img->name}} <a style="float: right" href="{{route('img',$img->id)}}" class="btn-sm btn-primary" role="button">查看高清图</a></h4>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
        <hr>
    @endforeach

</div> <!-- /container -->


</body>
</html>
