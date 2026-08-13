jQuery(function($){
  $('#kml-test-send').on('click', function(e){
    e.preventDefault();
    var mobile = $('#kml-test-mobile').val();
    $('#kml-test-result').removeClass('ok err').text('در حال ارسال...');
    $.post(KML_ADMIN.ajaxurl,{action:'kml_test_sms',nonce:KML_ADMIN.nonce,mobile:mobile},function(r){
      $('#kml-test-result').addClass(r.success?'ok':'err').text(r.success?r.data.message:(r.data&&r.data.message?r.data.message:'خطا'));
    });
  });

  var $wrap = $('.kml-admin-wrap');
  var $form = $wrap.find('form').first();
  var $table = $form.find('table.form-table').first();
  var $activeTabInput = $form.find('input[name="kml_active_tab"]').first();
  if (!$table.length || $wrap.data('kmlModernized')) return;
  $wrap.data('kmlModernized', true);

  $wrap.find('h1').first().next('p').addBack().wrapAll('<div class="kml-admin-hero"><div></div></div>');
  $('.kml-admin-hero').append('<div class="kml-admin-hero-badge"><span>تنظیمات ورود موبایلی</span></div>');

  var sections = [];
  var current = {id:'sms', title:'📨 پیامک و امنیت', desc:'تنظیمات پنل پیامک، کد تایید، محدودیت ارسال و متن چک‌اوت', rows:[]};
  sections.push(current);

  $table.find('> tbody > tr').each(function(){
    var $tr = $(this);
    var $h2 = $tr.find('h2').first();
    if ($h2.length) {
      var title = $.trim($h2.text());
      var id = 'sec-' + sections.length;
      var desc = 'تنظیمات مربوط به ' + title.replace(/^[^\wآ-ی]+/,'');
      current = {id:id, title:title, desc:desc, rows:[]};
      sections.push(current);
      $tr.remove();
    } else {
      current.rows.push($tr.detach());
    }
  });

  var $layout = $('<div class="kml-admin-layout"></div>');
  var $tabs = $('<div class="kml-admin-tabs" role="tablist"></div>');
  var $contents = $('<div class="kml-admin-sections"></div>');

  $.each(sections, function(i, sec){
    if (!sec.rows.length) return;
    var icon = sec.title.match(/^\S+/) ? sec.title.match(/^\S+/)[0] : '⚙️';
    var cleanTitle = sec.title.replace(/^\S+\s*/, '');
    var $btn = $('<button type="button" class="kml-admin-tab" role="tab"></button>').attr('data-target', sec.id).html('<span class="kml-admin-tab-icon">'+icon+'</span><span>'+cleanTitle+'</span>');
    if (i===0) $btn.addClass('is-active');
    $tabs.append($btn);

    var $sec = $('<section class="kml-admin-section"></section>').attr('id', sec.id);
    if (i===0) $sec.addClass('is-active');
    $sec.append('<div class="kml-admin-section-head"><h2>'+sec.title+'</h2><p>'+sec.desc+'</p></div>');
    var $newTable = $('<table class="form-table" role="presentation"><tbody></tbody></table>');
    $.each(sec.rows, function(_, row){ $newTable.find('tbody').append(row); });
    $sec.append($newTable);
    $sec.append('<div class="kml-admin-section-foot"><button type="submit" class="button button-primary">ذخیره تنظیمات</button></div>');
    $contents.append($sec);
  });

  $layout.append($tabs).append($contents);
  $table.replaceWith($layout);
  $form.find('p.submit').wrap('<div class="kml-admin-submit"></div>');

  $tabs.on('click', '.kml-admin-tab', function(){
    var target = $(this).data('target');
    $('.kml-admin-tab').removeClass('is-active');
    $(this).addClass('is-active');
    $('.kml-admin-section').removeClass('is-active');
    $('#'+target).addClass('is-active');
    if ($activeTabInput.length) $activeTabInput.val(target);
    try { localStorage.setItem('kml_admin_tab', target); } catch(e) {}
  });

  try {
    var saved = localStorage.getItem('kml_admin_tab');
    if (saved && $('#'+saved).length) $('.kml-admin-tab[data-target="'+saved+'"]').trigger('click');
  } catch(e) {}

  $form.on('submit', function(){
    var $activeTab = $tabs.find('.kml-admin-tab.is-active').first();
    if ($activeTab.length && $activeTabInput.length) $activeTabInput.val($activeTab.data('target'));
  });

  // Create nicer cards for existing test/shortcode elements without changing PHP logic.
  var $testH = $wrap.find('h2').filter(function(){return $.trim($(this).text()) === 'تست ارسال پیامک';}).first();
  if ($testH.length) {
    var $testCard = $('<div class="kml-test-card"></div>');
    $testH.prev('hr').remove();
    $testH.before($testCard);
    $testCard.append($testH);
    $testCard.append($testH.next('p'));
    var $row = $('<div class="kml-test-row"></div>');
    $row.append($('#kml-test-mobile'), $('#kml-test-send'));
    $testCard.append($row, $('#kml-test-result'));
  }
  var $shortH = $wrap.find('h2').filter(function(){return $.trim($(this).text()) === 'شورت‌کد';}).first();
  if ($shortH.length) {
    var $shortCard = $('<div class="kml-shortcode-card"></div>');
    $shortH.prev('hr').remove();
    $shortH.before($shortCard);
    $shortCard.append($shortH, $shortH.next('code'));
  }
});
