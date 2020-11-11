<nav class="navbar navbar-default">
  <div class="container">
    <div class="navbar-header">
      <a class="navbar-brand" href="#">
        <img alt="Brand" src="{{company()->Image}}" style="height: 58px">
      </a>
    </div>
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
        <ul class="nav navbar-nav navbar-right">
            @if (Auth::check())
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{Auth::user()->UserName}}</a>
                <ul class="dropdown-menu">
                    <li><a href="{{config('app.admin_url')}}/#ViewID=User_DetailView&ObjectKey={{Auth::user()->Oid}}&ObjectClassName=Cloud_ERP.Module.BusinessObjects.Security.User&mode=View">My Details</a></li>
                </ul>
            </li>
            @endif
        </ul>
    </div>
  </div>
</nav>