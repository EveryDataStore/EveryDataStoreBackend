<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
        <link rel="icon" type="image/png" sizes="16x16" href="{$BaseHref}//_resources/everydatastore/assets/img/favicon.ico">
        <title><%t TopNav.SITE_NAME 'everydatastore' %> : <%t SilverStripe\\Security\\Security.LOSTPASSWORDHEADER 'Lost Password' %></title>
        <!-- Bootstrap Core CSS -->
        <link href="{$BaseHref}//_resources/everydatastore/assets/css/bootstrap.min.css" rel="stylesheet">
        <!--<link href="{$BaseHref}//_resources/everydatastore/assets/css/bootstrap-icons.css" rel="stylesheet">-->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
        <!-- animation CSS -->
        <link href="{$BaseHref}//_resources/everydatastore/assets/plugins/animate.css" rel="stylesheet">
        <!-- Custom CSS -->
        <link href="{$BaseHref}//_resources/everydatastore/assets/css/style.css" rel="stylesheet">
        <!-- color CSS -->
        <link href="{$BaseHref}//_resources/everydatastore/assets/css/colors/blue.css" id="theme"  rel="stylesheet">
        <!-- custom CSS -->
        <link href="{$BaseHref}//_resources/everydatastore/assets/css/custom.css" id="theme"  rel="stylesheet">
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body id="page_lostpassword">
        <section id="wrapper" class="login-register">
        <div class="container-fluid">
          <div class="row">
            <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-12">
                <div class="login-box login-sidebar">
                    <div class="white-box lost-password">
                        <div class="text-center m-b-15">
                            <a href="{$BaseHref}//Security/login?BackURL={$BaseHref}//" class="text-center db"><img class="w-50" src="{$BaseHref}//_resources/everydatastore/assets/img/logo.png" alt="Home"></a>
                        </div>

                        <% if $Content %>
                                    <div class="alert alert-primary lost-pwd-content">$Content</div>
                        <% end_if %>

                        $Form

                        <div id="back_to_login">
                            <a href="{$BaseHref}//Security/login?BackURL={$BaseHref}//"><span class="bi bi-arrow-return-left"> </span><%t Action.BACK_TO_LOGIN 'Back to Login' %></a>
                        </div>

                    </div>
                </div>
            </div>
          </div>
        </div>
        </section>
        <!-- bootstrap bundle -->
        <script src="{$BaseHref}//_resources/everydatastore/assets/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
        <!-- jQuery -->
        <script src="{$BaseHref}//_resources/everydatastore/assets/js/jquery.min.js"></script>
        <!-- Bootstrap Core JavaScript -->
        <script src="{$BaseHref}//_resources/everydatastore/assets/plugins/bootstrap/dist/js/bootstrap.min.js"></script>
        <!-- Menu Plugin JavaScript -->
        <script src="{$BaseHref}//_resources/everydatastore/assets/plugins/sidebar-nav/dist/sidebar-nav.min.js"></script>
        <!--slimscroll JavaScript -->
        <script src="{$BaseHref}//_resources/everydatastore/assets/plugins/jquery.slimscroll.js"></script>
        <!--Wave Effects -->
        <script src="{$BaseHref}//_resources/everydatastore/assets/plugins/waves.js"></script>
        <!-- Custom Theme JavaScript -->
        <script src="{$BaseHref}//_resources/everydatastore/assets/js/custom.js"></script>
        <script>
            jQuery.noConflict();
            (function ($) {
                $(document).ready(function () {
                    $('#LostPasswordForm_lostPasswordForm').addClass('form-horizontal form-material');
                    $('#LostPasswordForm_lostPasswordForm_Email').addClass('form-control').attr("placeholder", "Email");
                    $('label.left').remove();
                    $('.field').addClass('m-b-15');
                    $('#LostPasswordForm_lostPasswordForm_action_forgotPassword').addClass('btn btn-info btn-block m-b-15');
                });
            }(jQuery));
        </script>
    </body>
</html>

