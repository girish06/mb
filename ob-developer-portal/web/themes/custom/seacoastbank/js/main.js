/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function($, Drupal){
$(document).ready(function(){
 var url = $(".delightvideo").attr('src');
 var vid= document.getElementById('myvideo');

 /* Assign empty url value to the iframe src attribute when modal hide, which stop the video playing */
    $("#modal1").on('hide.bs.modal', function(){
        $(".delightvideo").attr('src', url);
    });
    
    /* Assign the initially stored url back to the iframe src
    attribute when modal is displayed again */
    $("#modal1").on('show.bs.modal', function(e){
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
//   $('.navbar-nav').click(function(event){
//       alert('on click');
//     $(document).scrollDown(500) ;// any value you need
//      
//       $('html, .region-content').animate({scrollTop: '+=150px'}, 800);
//   
//  
//   
//     });
});
})(jQuery), Drupal;
  