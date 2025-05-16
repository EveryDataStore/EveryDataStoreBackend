<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
        <link rel="icon" type="image/png" sizes="16x16" href="{$BaseHref}/_resources/everydatastore/assets/img/favicon.ico">
        <title><%t TopNav.SITE_NAME 'everydatastore' %>: <%t SilverStripe\\Security\\CMSSecurity.LOGIN_TITLE 'Return to where you left off by logging back in' %></title>
        <!-- Bootstrap Core CSS -->
        <link href="{$BaseHref}/_resources/everydatastore/assets/plugins/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
        <!-- animation CSS -->
        <link href="{$BaseHref}/_resources/everydatastore/assets/plugins/animate.css" rel="stylesheet">
        <!-- Custom CSS -->
        <link href="{$BaseHref}/_resources/everydatastore/assets/css/style.css" rel="stylesheet">
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body id="page_login">
        <section id="wrapper" class="login-register home-after-login">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-12" style="margin-top: 30px; margin-bottom: 30px;">
                        <a href="{$BaseHref}/Security/login?BackURL={$BaseHref}/" class="text-center w-100 db"><img src="{$BaseHref}/_resources/everydatastore/assets/img/logo.png" alt="Home"></a>
                    </div>
                    <div class="col-xl-12 col-lg-12 col-md-12 col-12 every-data-store-data">
                        <a href="admin" target="_blank"><i class="bi bi-arrow-right"></i>Admin Panel</a>
                        <a href="https://everydatastore.org/en/documentation" target="_blank"><i class="bi bi-arrow-right"></i> EveryDataStore Documentation</a>
                        <a href="https://github.com/EveryDataStore" target="_blank"><i class="bi bi-arrow-right"></i> EveryDataStore GitHub</a>
                         <a href="{$BaseHref}/restful/info" target="_blank"><i class="bi bi-arrow-right"></i> EveryRESTfulAPI License Info</a>
                        <a href="https://docs.silverstripe.org/en/5/" target="_blank"><i class="bi bi-arrow-right"></i> SilverStripe Documentation</a>
                    </div>
                </div>
            </div>
        </section>
        <!-- jQuery -->
        <script src="{$BaseHref}/_resources/everydatastore/assets/js/jquery/jquery.min.js"></script>
        <!-- Bootstrap Core JavaScript -->
        <script src="{$BaseHref}/_resources/everydatastore/assets/plugins/bootstrap/dist/js/bootstrap.min.js"></script>
        <!-- Custom Theme JavaScript -->
        <script src="{$BaseHref}/_resources/everydatastore/assets/js/custom.js"></script>

    </body>
</html>
