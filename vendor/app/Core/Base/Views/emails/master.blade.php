@include('Core\Base::emails.header' )

<div style="margin:0px auto;max-width:600px;">
  <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;"
  align="center" border="0">
    <tbody>
      <tr>
        <td style="text-align:center;vertical-align:top;direction:ltr;font-size:0px;padding:0px;">
          <!--[if mso | IE]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td style="vertical-align:top;width:600px;">
                <![endif]-->
                <div class="mj-column-per-100 outlook-group-fix" style="vertical-align:top;display:inline-block;direction:ltr;font-size:13px;text-align:left;width:100%;">
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                    <tbody>
                      <tr>
                        <td style="word-wrap:break-word;font-size:0px;padding:0px;" align="left">
                          <div style="cursor:auto;color:#5d7079;font-family:TW-Averta-Regular, Averta, Helvetica, Arial;font-size:16px;line-height:24px;letter-spacing:0.4px;text-align:left;">
                            <p>@yield('greeting')</p>
                            <p class="hero">@yield('title')</p>
                            <p>@yield('content')</p>
                            <hr />
                            <p>@yield('regards')</p>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <!--[if mso | IE]>
                </td>
              </tr>
            </table>
          <![endif]-->
        </td>
      </tr>
    </tbody>
  </table>
</div>

@include('Core\Base::emails.footer' )