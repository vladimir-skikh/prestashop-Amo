{**
 *  PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright  PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{block name='header_banner'}
  <div class="header-banner">
    {if isset($fullwidth_hook.displayBanner) AND $fullwidth_hook.displayBanner == 0}
      <div class="container">
      {/if}
        <div class="inner">
          {hook h='displayBanner'}
          {if Context::getContext()->isMobile() }
              <div style="
                  max-width: 1000px;
                  width: 100%;
                  margin: 0 auto;
                  display: flex;
                  justify-content: flex-end;
                  padding: 0 20px;
                  margin: 0  auto;
              ">
                {hook h="displayCityChangeMobile"}
              </div>
          {else}
              <div style="
                  max-width: 1000px;
                  width: 100%;
                  margin: 0 auto;
                  display: flex;
                  justify-content: flex-end;
                  padding: 0 20px;
                  margin: 0  auto;
              ">
                {hook h="displayCityChange"}
              </div>
          {/if}
        </div>
    {if isset($fullwidth_hook.displayBanner) AND $fullwidth_hook.displayBanner == 0}
      </div>
      {/if}
  </div>
{/block}

{block name='header_nav'}
  <nav class="header-nav">
    <div class="topnav">
      {if isset($fullwidth_hook.displayNav1) AND $fullwidth_hook.displayNav1 == 0}
      <div class="container">
      {/if}
        <div class="inner">{hook h='displayNav1'}</div>
      {if isset($fullwidth_hook.displayNav1) AND $fullwidth_hook.displayNav1 == 0}
      </div>
      {/if}
    </div>
    <div class="bottomnav">
      {if isset($fullwidth_hook.displayNav2) AND $fullwidth_hook.displayNav2 == 0}
        <div class="container">
      {/if}
        <div class="inner">{hook h='displayNav2'}</div>
      {if isset($fullwidth_hook.displayNav2) AND $fullwidth_hook.displayNav2 == 0}
        </div>
      {/if}
    </div>
  </nav>
{/block}

{block name='header_top'}
  <div class="header-top">
    {if isset($fullwidth_hook.displayTop) AND $fullwidth_hook.displayTop == 0}
          <div class="container">
        {/if}
      <div class="inner">{hook h='displayTop'}</div>
        {if isset($fullwidth_hook.displayTop) AND $fullwidth_hook.displayTop == 0}
          </div>
        {/if}
  </div>
  {hook h='displayNavFullWidth'}
{/block}
{literal}
<!-- Yandex.Metrika counter -->
<script type="text/javascript" >
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym(54225943, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true,
        webvisor:true,
        trackHash:true,
        ecommerce:"dataLayer"
   });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/54225943" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
<!-- Top100 (Kraken) Counter -->
<script>
    (function (w, d, c) {
    (w[c] = w[c] || []).push(function() {
        var options = {
            project: 6986664,
        };
        try {
            w.top100Counter = new top100(options);
        } catch(e) { }
    });
    var n = d.getElementsByTagName("script")[0],
    s = d.createElement("script"),
    f = function () { n.parentNode.insertBefore(s, n); };
    s.type = "text/javascript";
    s.async = true;
    s.src =
    (d.location.protocol == "https:" ? "https:" : "http:") +
    "//st.top100.ru/top100/top100.js";

    if (w.opera == "[object Opera]") {
    d.addEventListener("DOMContentLoaded", f, false);
} else { f(); }
})(window, document, "_top100q");
</script>
<noscript>
  <img src="//counter.rambler.ru/top100.cnt?pid=6986664" alt="Топ-100" />
</noscript>
<!-- END Top100 (Kraken) Counter -->
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-142784656-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-142784656-1');
</script>
{/literal}