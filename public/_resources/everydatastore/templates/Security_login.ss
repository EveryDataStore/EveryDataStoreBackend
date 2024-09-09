<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
       <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
        <link rel="icon" type="image/png" sizes="16x16" href="{$BaseHref}/_resources/everydatastore/assets/img/favicon.ico">
        <title><%t TopNav.SITE_NAME 'everydatastore' %>: <%t SilverStripe\\Security\\CMSSecurity.LOGIN_TITLE 'Return to where you left off by logging back in' %></title>
        <!-- Bootstrap Core CSS -->
        <link href="{$BaseHref}/_resources/everydatastore/assets/css/bootstrap.min.css" rel="stylesheet">

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
        <section id="wrapper" class="login-register">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-12">
                        <div class="login-box login-sidebar">
                            <div class="white-box">
                                <div class="text-center m-b-15">
                                    <a href="{$BaseHref}/Security/login?BackURL={$BaseHref}/" class="text-center db"><img class="w-100" src="{$BaseHref}/_resources/everydatastore/assets/img/logo.png" alt="Home2"></a>
                                </div>
                                <% if $Content %>
                                    <div class="alert alert-primary">$Content</div>
                                <% end_if %>
                                $Form
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
        <script src="{$BaseHref}/_resources/everydatastore/assets/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
        <script src="{$BaseHref}/_resources/everydatastore/assets/js/jquery.min.js"></script>
        <script>
            jQuery.noConflict();
            (function ($) {
                $(document).ready(function () {
                    $('#MemberLoginForm_LoginForm').addClass('form-horizontal form-material');
                    $('#MemberLoginForm_LoginForm_Password').addClass('form-control').attr("placeholder", "<%t   SilverStripe\\Security\\Member.PASSWORD 'Password' %>");
                    $('#MemberLoginForm_LoginForm_Email').addClass('form-control').attr("placeholder", "<%t   SilverStripe\\Security\\Member.EMAIL 'Email' %>");
                    $('label.left').remove();
                    $('.field').addClass('m-b-15');
                    $('#MemberLoginForm_LoginForm_action_doLogin').addClass('btn btn-info btn-block m-b-15');
                    var logout_btn = $('#MemberLoginForm_LoginForm_action_logout'),
                            forgot_password_btn = $('#ForgotPassword a');

                    if (forgot_password_btn.size() > 0) {
                        forgot_password_btn.addClass('btn btn-primary btn-block m-b-15');
                    }

                });
            }(jQuery));
        </script>
    </body>
</html>