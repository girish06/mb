/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

(function ($, Drupal) {
  $(document).ready(function () {
    setTimeout(function() {
      $(".home-page-top-bar img").each(function() {
        var imageUrl = $(this).attr("src");
        $(this).closest(".home-page-top-bar").css("background-image", "url(" + imageUrl + ")");
      });
    }, 1500);

    $('.navbar-toggle').click(function (e) {
      if($('body').hasClass('mn-active')) {
        $('body').removeClass('mn-active')
      }else {
        $('body').addClass('mn-active');
      }
    });

    $(".left-side-image img").each(function(){
      var imageUrl = $(this).attr("src");
      $(this).closest(".left-side-image").css("background-image", "url(" + imageUrl + ")")
    });
    
    if ($(window).width() > 992) {
      $('.left-side-image') .css({'height': (($(window).height()) + 160)+'px'});
      $(window).resize(function() {
        $('.left-side-image') .css({'height': (($(window).height()) + 160)+'px'});
      });
    }

    var url = $(".delightvideo").attr('src');
    var vid = document.getElementById('myvideo');

    /* Assign empty url value to the iframe src attribute when modal hide, which stop the video playing */
    $("#modal1").on('hide.bs.modal', function () {
      $(".delightvideo").attr('src', url);
    });

    /* Assign the initially stored url back to the iframe src
    attribute when modal is displayed again */
    $("#modal1").on('show.bs.modal', function (e) {
      var target = e.relatedTarget;
      var url = $(target).find('source').attr('src');
      console.log(url);
      $("#popupvid").attr('src', url);
    });

    $(".video").click(function () {
      var modal = $(this).data("target");
      var videoSRC = $(this).attr("data-video");
      videoSRCauto = videoSRC;
      $(modal + ' video').attr('src', videoSRCauto);
      $(modal + ' button.close').click(function () {
        $(modal + ' video').attr('src', '');
      });
    });
    $('.switch').click(function (e) {
      e.preventDefault();
      if ($('label.switch .swagger-toggle').attr('checked') == undefined || $('label.switch .swagger-toggle').attr('checked') == '') {
        $('label.switch .swagger-toggle').attr('checked', 'checked');
        $('.redoc-file').css("display", "block");
        $('.swagger-file').css("display", "none");
      } else {
        $('label.switch .swagger-toggle').removeAttr('checked');
        $('.redoc-file').css("display", "none");
        $('.swagger-file').css("display", "block");
      }
    });
    $(".api-product-padding").on('click', ".user-authorize-btn", function () {
      setTimeout(function () {
        $(".modal-dialog-ux").addClass('user-container');
      }, 30);
    });
    $(".api-product-padding").on('click', ".tpp-authorize-btn", function () {
      setTimeout(function () {
        $(".modal-dialog-ux").addClass('tpp-container');
      }, 30);

    });


    // $("body").on('each',".jws-sign",function (e){
    //     e.preventDefault();
    //     var $link = $(this);
    //     var $dialog = $('<div></div>')
    //         .load($link.attr('href'))
    //         .dialog({
    //             autoOpen: false,
    //             title: $link.attr('title'),
    //             width: 500,
    //             height: 300
    //         });
    //     $("body").on('click',".jws-sign",function (e){
    //         console.log('clicked on jws button');
    //         e.preventDefault();
    //         $dialog.dialog('open');
    //         return false;
    //     });
    // });

    // $("body").on('click',".jws-sign",function (){
    //     var $link = $(this);
    //     var $dialog = $('<div></div>').load("/ob-developer-portal_back/web/jws_signature_creation/create").dialog({
    //        // title: $link.attr('title'),
    //         width: 500,
    //         height: 300
    //     });
    //     return false
    // });

    //
    $(window).one("scroll", function () {
      if ($('header').hasClass('stickynav-active')) {
        $('body').addClass('js--padding-fix');
      }
    });


  });
})(jQuery), Drupal;

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.LotusBehavior = {

    attach: function (context, settings) {
      setTimeout(function() {
        $(".home-page-top-bar img").each(function() {
          var imageUrl = $(this).attr("src");
          $(this).closest(".home-page-top-bar").css("background-image", "url(" + imageUrl + ")");
        });
      }, 1200);

      jQuery(".panel-title").text(function () {
        return jQuery(this).text().replace("Credential", " ");
      });
      var jwssecretkey = drupalSettings.mymoduleComputedData;
      // alert(jwssecretkey);
      $("#block-jwssignatureblock").hide();
      $('form#jsw-signature-key').submit(function (e) {
        e.preventDefault();
        var secretkey = $('input#edit-jwt-secret-key').val();
        alert(secretkey);
      });
      // can access setting from 'drupalSettings';
     $("body").on('click', ".try-out__btn", function () {
        $("#block-jwssignatureblock").dialog({
          autoOpen: false,
          show: {
            effect: "blind",
            duration: 1000
          },
          hide: {
            effect: "explode",
            duration: 1000
          }
        });
        $("#block-jwssignatureblock").dialog('close');
        $("body").on('click', ".jws-sign", function () {
          $("#block-jwssignatureblock").show();
          $("#block-jwssignatureblock").dialog('open');
          $('form#jsw-signature').submit(function (e) {
            e.preventDefault();
            // $(this).parents('form').submit();

            //console.log('clicked generate');
            //  var jwsdata = drupalSettings.mymoduleComputedData;
            var message = $('textarea#edit-payload').val();
            var header = {"alg": "HS256", "typ": "JWT"};
            var secret = "nZIRzqNNMdf9IpzWrK4lKpQr";

            function base64url(source) {
              // Encode in classical base64
              encodedSource = CryptoJS.enc.Base64.stringify(source);

              // Remove padding equal characters
              encodedSource = encodedSource.replace(/=+$/, '');

              // Replace characters according to base64url specifications
              encodedSource = encodedSource.replace(/\+/g, '-');
              encodedSource = encodedSource.replace(/\//g, '_');

              return encodedSource;
            }

            var stringifiedHeader = CryptoJS.enc.Utf8.parse(JSON.stringify(header));
            var encodedHeader = base64url(stringifiedHeader);
            var stringifiedDatasp = jQuery.parseJSON(message);
            var stringifiedDatas = JSON.stringify(stringifiedDatasp);
            var stringifiedData = CryptoJS.enc.Utf8.parse(stringifiedDatas);
            var encodedData = base64url(stringifiedData);
            var signature = encodedHeader + "." + encodedData;
            signature = CryptoJS.HmacSHA256(signature, jwssecretkey);
            signature = base64url(signature);
            $("#block-jwssignatureblock").dialog('close');

            // var jwssign = drupalSettings.jwt;
            console.log(encodedData);
          //  $(".x-jws-signature input:text").click();



            $(".x-jws-signature input:text").append(" ");
           $('.x-jws-signature input:text').trigger(jQuery.Event('keypress', {which: 13}));
           $("body").on('keypress',".x-jws-signature input:text",function (){
           // $(".x-jws-signature input:text").keypress(function () {
            //  $(".x-jws-signature input:text").val(encodedHeader + '..' + signature);
           console.log('key pressed');
           $(".x-jws-signature input:text").append(" ");
           $('td.x-jws-signature input').attr('value', encodedHeader + '..' + signature);
           $('td.x-jws-signature input').val(encodedHeader + '..' + signature);
           $('td.x-jws-signature input').trigger('change');


           });


          });

        });
      });

    }
  };
})(jQuery, Drupal, drupalSettings);
  