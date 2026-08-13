jQuery(function($){
  var webOtpAbort = null;

  function msg(box, text, ok){ box.find('.kml-message').removeClass('ok err').addClass(ok?'ok':'err').text(text); }
  function normalizeDigits(value){
    var fa='۰۱۲۳۴۵۶۷۸۹', ar='٠١٢٣٤٥٦٧٨٩';
    return String(value || '').replace(/[۰-۹]/g,function(d){return fa.indexOf(d);}).replace(/[٠-٩]/g,function(d){return ar.indexOf(d);}).replace(/[^0-9]/g,'');
  }
  function codeLength(){
    var n = parseInt((window.KML && KML.code_length) || 5, 10);
    if(!n || n < 4) n = 5;
    if(n > 8) n = 8;
    return n;
  }
  function ensureOtpBoxes(box){
    var len = codeLength();
    box.find('.kml-otp-real').attr({'maxlength': len, 'autocomplete':'one-time-code', 'inputmode':'numeric', 'pattern':'[0-9]*'});
    box.find('.kml-otp-boxes').each(function(){
      var holder = $(this);
      if(holder.children().length === len) return;
      holder.empty();
      for(var i=0;i<len;i++){ holder.append('<span class="kml-otp-box"></span>'); }
    });
  }
  function syncOtpBoxes(input){
    var inp = $(input), box = inp.closest('.kml-box'), val = normalizeDigits(inp.val()).slice(0, codeLength());
    inp.val(val);
    ensureOtpBoxes(box);
    box.find('.kml-otp-box').each(function(i){
      var ch = val.charAt(i);
      $(this).text(ch).toggleClass('is-filled', !!ch).toggleClass('is-active', i === val.length && val.length < codeLength());
    });
    return val;
  }
  function maskMobile(value){
    var v = String(value || '').trim();
    var digits = normalizeDigits(v);
    if(digits.length >= 10){ return digits.slice(0,4) + '***' + digits.slice(-4); }
    if(v.indexOf('@') > -1){
      var p = v.split('@');
      return (p[0] || '').slice(0,2) + '***@' + (p[1] || '');
    }
    return v.length > 4 ? v.slice(0,2) + '***' + v.slice(-2) : v;
  }
  function focusOtp(box){
    setTimeout(function(){ box.find('.kml-otp-real').trigger('focus'); }, 80);
  }
  function showOtpOnly(box){
    var entered = box.find('.kml-mobile').val();
    box.find('.kml-masked-mobile').text(maskMobile(entered));
    box.find('.kml-mobile-confirm').show();
    box.find('.kml-step-mobile').slideUp(150);
    box.find('.kml-step-code').slideDown(160, function(){
      ensureOtpBoxes(box);
      box.find('.kml-code').val('');
      syncOtpBoxes(box.find('.kml-code')[0]);
      focusOtp(box);
      startWebOtp(box);
    });
  }
  function resetToMobile(box){
    if(webOtpAbort){ try{ webOtpAbort.abort(); }catch(e){} }
    box.data('kml-verifying', false);
    box.find('.kml-code').val('');
    box.find('.kml-step-code,.kml-profile-step').slideUp(120);
    box.find('.kml-step-mobile').slideDown(150, function(){ box.find('.kml-mobile').trigger('focus'); });
    box.find('.kml-message').removeClass('ok err').text('');
  }
  function countdown(box){
    var sec = parseInt(KML.resend_wait || 120, 10);
    var btn = box.find('.kml-resend'), label = box.find('.kml-countdown');
    if(box.data('kml-countdown-timer')) clearInterval(box.data('kml-countdown-timer'));
    btn.prop('disabled', true);
    var t = setInterval(function(){
      var m = Math.floor(sec/60), r = sec % 60;
      label.text('ارسال مجدد تا ' + String(m).padStart(2,'0') + ':' + String(r).padStart(2,'0'));
      sec--;
      if(sec < 0){ clearInterval(t); label.text(''); btn.prop('disabled', false); }
    },1000);
    box.data('kml-countdown-timer', t);
  }

  function verifyOtp(box){
    if(box.data('kml-verifying')) return;
    var mobile=box.find('.kml-mobile').val(), code=normalizeDigits(box.find('.kml-code').val());
    if(code.length < codeLength()) return;
    box.data('kml-verifying', true);
    box.find('.kml-verify').prop('disabled', true).addClass('is-loading');
    msg(box,'در حال ورود خودکار...',true);
    $.post(KML.ajaxurl,{action:'kml_verify_otp',nonce:KML.nonce,mobile:mobile,code:code,redirect:box.data('redirect')||window.location.href},function(r){
      if(r.success){
        msg(box,r.data.message,true);
        if(r.data.profile_nonce){ KML.profile_nonce = r.data.profile_nonce; }
        if(r.data.profile_token){ KML.profile_token = r.data.profile_token; }
        if(r.data.needs_profile){
          box.find('.kml-step-mobile,.kml-step-code').slideUp(120);
          box.find('.kml-profile-step').slideDown(150);
          return;
        }
        window.location.href = r.data.redirect || window.location.href;
      }
      else {
        box.data('kml-verifying', false);
        box.find('.kml-verify').prop('disabled', false).removeClass('is-loading');
        msg(box,(r.data&&r.data.message)?r.data.message:'کد تایید صحیح نیست',false);
        if(r.data && r.data.code === 'need_mobile_new'){
          box.find('.kml-mobile').val('').attr('placeholder','شماره موبایل خود را وارد کنید').focus();
          box.find('.kml-step-code').slideUp(120);
          box.find('.kml-step-mobile').slideDown(150);
        }
      }
    }).fail(function(){
      box.data('kml-verifying', false);
      box.find('.kml-verify').prop('disabled', false).removeClass('is-loading');
      msg(box,'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.',false);
    });
  }

  function startWebOtp(box){
    // Android Chrome WebOTP. SMS text must end with: @your-domain.com #12345
    if(!('OTPCredential' in window) || !navigator.credentials || !window.AbortController) return;
    if(webOtpAbort){ try{ webOtpAbort.abort(); }catch(e){} }
    webOtpAbort = new AbortController();
    var timeout = setTimeout(function(){ try{ webOtpAbort.abort(); }catch(e){} }, Math.max(60000, (parseInt(KML.resend_wait || 120,10) + 15) * 1000));
    navigator.credentials.get({ otp: { transport:['sms'] }, signal: webOtpAbort.signal }).then(function(otp){
      clearTimeout(timeout);
      if(otp && otp.code){
        var input = box.find('.kml-code');
        input.val(normalizeDigits(otp.code).slice(0, codeLength()));
        syncOtpBoxes(input[0]);
        verifyOtp(box);
      }
    }).catch(function(){ clearTimeout(timeout); });
  }

  $(document).on('click','.kml-otp-wrap,.kml-otp-boxes,.kml-otp-box',function(){
    $(this).closest('.kml-box').find('.kml-otp-real').trigger('focus');
  });

  var autoTimers = new WeakMap();
  $(document).on('input paste keyup','.kml-otp-real',function(){
    var input = this, box = $(input).closest('.kml-box'), val = syncOtpBoxes(input);
    if(val.length === codeLength()){
      clearTimeout(autoTimers.get(input));
      var timer = setTimeout(function(){ verifyOtp(box); }, 180);
      autoTimers.set(input, timer);
    }
  });
  $(document).on('keydown','.kml-otp-real',function(e){
    if(e.key === 'Enter'){
      e.preventDefault();
      verifyOtp($(this).closest('.kml-box'));
    }
  });

  $(document).on('click','.kml-forgot-link',function(){
    var box=$(this).closest('.kml-box');
    msg(box,'شماره موبایل، ایمیل یا نام کاربری قبلی خود را وارد کنید تا کد ورود پیامک شود.',true);
    box.find('.kml-mobile').focus();
  });

  $(document).on('click','.kml-send,.kml-resend',function(){
    var box=$(this).closest('.kml-box'), mobile=box.find('.kml-mobile').val();
    box.data('kml-verifying', false);
    box.find('.kml-verify').prop('disabled', false).removeClass('is-loading');
    msg(box,'در حال ارسال کد...',true);
    $.post(KML.ajaxurl,{action:'kml_send_otp',nonce:KML.nonce,mobile:mobile},function(r){
      if(r.success){
        msg(box,r.data.message || 'کد تایید ارسال شد.',true);
        showOtpOnly(box);
        countdown(box);
      }
      else {
        msg(box,(r.data&&r.data.message)?r.data.message:'خطا در ارسال کد',false);
        if(r.data && r.data.code === 'need_mobile_new'){
          box.find('.kml-mobile').val('').attr('placeholder','شماره موبایل خود را وارد کنید').focus();
          box.find('.kml-step-code').slideUp(120);
          box.find('.kml-step-mobile').slideDown(150);
        }
      }
    }).fail(function(){ msg(box,'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.',false); });
  });

  $(document).on('click','.kml-change-mobile',function(){
    resetToMobile($(this).closest('.kml-box'));
  });

  $(document).on('click','.kml-verify',function(){ verifyOtp($(this).closest('.kml-box')); });

  $(document).on('click','.kml-save-profile',function(){
    var box=$(this).closest('.kml-box');
    msg(box,'در حال ذخیره مشخصات...',true);
    $.post(KML.ajaxurl,{
      action:'kml_complete_profile',
      nonce:(KML.profile_nonce || KML.nonce),
      profile_token:(KML.profile_token || ''),
      first_name:box.find('.kml-first-name').val(),
      last_name:box.find('.kml-last-name').val(),
      email:box.find('.kml-email').val(),
      redirect:box.data('redirect')||window.location.href
    },function(r){
      if(r.success){ msg(box,r.data.message,true); window.location.href = r.data.redirect || window.location.href; }
      else msg(box,(r.data&&r.data.message)?r.data.message:'خطا در ذخیره مشخصات',false);
    }).fail(function(xhr){
      var text = 'خطا در ذخیره مشخصات. لطفاً صفحه را رفرش کنید و دوباره تلاش کنید.';
      if(xhr && xhr.status === 403){ text = 'دسترسی ذخیره مشخصات رد شد. صفحه را رفرش کنید و دوباره تلاش کنید.'; }
      msg(box,text,false);
    });
  });

  $('.kml-box').each(function(){ ensureOtpBoxes($(this)); syncOtpBoxes($(this).find('.kml-code')[0]); });
});

// v1.6.3 - Single-screen OTP UX + Support bottom sheet
jQuery(function($){
  $(document).on('click', '.kml-open-support-sheet', function(e){
    e.preventDefault();
    $('.kml-support-sheet').addClass('is-open').attr('aria-hidden','false');
    $('body').addClass('kml-support-open');
  });
  $(document).on('click', '.kml-close-support-sheet', function(e){
    e.preventDefault();
    $('.kml-support-sheet').removeClass('is-open').attr('aria-hidden','true');
    $('body').removeClass('kml-support-open');
  });
  $(document).on('keydown', function(e){
    if(e.key === 'Escape'){
      $('.kml-support-sheet').removeClass('is-open').attr('aria-hidden','true');
      $('body').removeClass('kml-support-open');
    }
  });
});
