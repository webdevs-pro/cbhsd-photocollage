<?php
/*
Plugin Name: CBHSD Photo Collage Shortcode
Version: 1.0
*/


add_shortcode('cbhsd-photocollage', function() {

   // prepare images array
   $args = array (
      'post_type' => 'attachment',
      'posts_per_page' => '50',
      'orderby' => 'rand',
      'tax_query' => array(
         array(
            'taxonomy' => WPMF_TAXO,
            'field' => 'term_id',
            'terms' => '8', // ID of WPMF folder
         ),
      ),
   );
   $images = get_posts( $args );

   $urls = array();
   foreach ($images as $image) {
         $urls[] = array(
            'small' => wp_get_attachment_image_url($image->ID, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($image->ID, 'medium'),
         );
   }
   $urls = json_encode($urls);


   ob_start(); // start buffering shortcode output

   ?>


   <script>

      window.scroll(0, 0);
      disableScroll();        

      var urls = JSON.parse('<?php echo $urls; ?>'); // pass php array to script
      
      jQuery(document).ready(function($) {

         // disable "prevent scroll while loading" in elementor editor
         if($('body').hasClass('elementor-editor-active')) {
            enableScroll();        
         }

         var rows = $(".fake_wrapper").css("grid-template-rows").split(" ").length;
         var cols = $(".fake_wrapper").css("grid-template-columns").split(" ").length;
         var $cont = $($('#collage'));
         var $wrapper = $($('.wrapper'));
         var window_width = $(window).width();
         var window_height = $(window).height();
         // small images on mobile
         if (window_width > 767 || window_height > 767) {
            var image_size = 'medium';
         } else {
            var image_size = 'small';
         }

         fillCollageCells(rows,cols); // build collage on load

         // rebuild collage on window resize
         $(window).resize(function() {
         var new_rows = $(".fake_wrapper").css("grid-template-rows").split(" ").length;
         var new_cols = $(".fake_wrapper").css("grid-template-columns").split(" ").length;
         if (new_rows != rows || new_cols != cols) {
            rows = new_rows; cols = new_cols;
            fillCollageCells(rows,cols);
         }
         });

         // build collage function
         function fillCollageCells(rows,cols) {

            var html = '<div class="wrapper">';
            var image_count = 0;

            // rows
            for (var r = 1; r < rows+1; r++) {
               html += '<div class="row">';
               // columns
               for (var c = 1; c < cols+1; c++) {
                  html += '<div class="item"><img src="'+urls[image_count][image_size]+'"/></div>';
                  image_count++;
               }
               html += '</div>';
            }
            html += '</div>';
            html_object = $('<div/>').html(html).contents();
            
            // randomize colunbs width
            var margin_ratio = Math.round(($(window).width()) / 100);
            for (var r = 1; r < rows+1; r++) {
               for (var c = 0; c < cols+1; c++) {
                  var rand_margin = randomRangeWithIncrements(margin_ratio, margin_ratio * 4, margin_ratio);
                  var rand_col = randomRangeWithIncrements(2, cols+1, 1);
                  var rand_dir = randomRangeWithIncrements(0, 2, 1);
                  var prev_item = $(html_object).find('.row:nth-child('+r+') .item:nth-child('+(rand_col-1)+')');
                  var next_item = $(html_object).find('.row:nth-child('+r+') .item:nth-child('+rand_col+')'); 
                  if(rand_dir == 0) {
                     $(prev_item).css('margin-right','-'+rand_margin+'px');
                     $(next_item).css('margin-left',rand_margin+'px');  
                  } else {
                     $(prev_item).css('margin-right',rand_margin+'px');
                     $(next_item).css('margin-left','-'+rand_margin+'px');       
                  }
               }
            }

            // remove old and append new collage wrapper
            $cont.find('.wrapper').remove();
            $cont.append(html_object);

            // image src on load randow fade dalay
            $('.item img').on('load',function() {
               console.log('imf load');
               $(this).css('transition-delay', Math.floor((Math.random() * 400) + 1)+'ms'); // 
               $(this).css('opacity', '1');
            });

         }


         // random image change
         var timer_count = 0;
         var timer = setInterval(function(){
            var items = $cont.find('.item');
            var rand_el = randomRangeWithIncrements(0, items.length, 1);
            var rand_image = randomRangeWithIncrements(0, 49, 1);
            $(items).eq(rand_el).children('img').css('transition-delay', '0ms');
            $(items).eq(rand_el).children('img').css('opacity', '0');
            setTimeout(function(){
               // $(items).eq(rand_el).children('img').attr('src', urls[rand_image]['small']);
               $(items).eq(rand_el).children('img').attr('src', urls[rand_image][image_size]);
            },400);
            timer_count++;
            if (timer_count > 20) {
               clearTimeout(timer);       
            }
         },5000);
         
         // prevent page scroll for 2.5s on load and then show scroll down icon
         setTimeout(function(){
            enableScroll();
            $('.scroll_down').css('opacity','1');
         },1500);
         
      });
      

      // randow with step function
      function randomRangeWithIncrements(min, max, inc) {
         min = min || 0;
         inc = inc || 1;
         if(!max) { return new Error('need to define a max');}
         return Math.floor(Math.random() * (max - min) / inc) * inc + min;
      }
      
      
      // DISABLE SCROLL FUNCTION
      // left: 37, up: 38, right: 39, down: 40,
      // spacebar: 32, pageup: 33, pagedown: 34, end: 35, home: 36
      var keys = {37: 1, 38: 1, 39: 1, 40: 1};
      function preventDefault(e) {
         e = e || window.event;
         if (e.preventDefault)
            e.preventDefault();
         e.returnValue = false;  
      }
      function preventDefaultForScrollKeys(e) {
         if (keys[e.keyCode]) {
            preventDefault(e);
            return false;
         }
      }
      function disableScroll() {
         if (window.addEventListener) // older FF
            window.addEventListener('DOMMouseScroll', preventDefault, false);
         document.addEventListener('wheel', preventDefault, {passive: false}); // Disable scrolling in Chrome
         window.onwheel = preventDefault; // modern standard
         window.onmousewheel = document.onmousewheel = preventDefault; // older browsers, IE
         window.ontouchmove  = preventDefault; // mobile
         document.onkeydown  = preventDefaultForScrollKeys;
      }
      function enableScroll() {
         if (window.removeEventListener)
            window.removeEventListener('DOMMouseScroll', preventDefault, false);
         document.removeEventListener('wheel', preventDefault, {passive: false}); // Enable scrolling in Chrome
         window.onmousewheel = document.onmousewheel = null; 
         window.onwheel = null; 
         window.ontouchmove = null;  
         document.onkeydown = null;  
      }
   
   
   </script>

   <style>
   #collage {
      height: 100vh;
      position: relative;
      overflow: hidden;
   }
   #collage .wrapper {
      display: grid; 
      position: absolute;
      top: 0;
      left: 0px;
      right: -200px;
      width: calc(100% + 200px);
      height: 100%;
   }
   #collage .fake_wrapper {
      display: grid;
      position: absolute;
      top: 0;
      left: 0px;
      right: -200px;
      width: calc(100% + 200px);
      height: 100%;
   }
   #collage .row {
      display: grid;
   }
   #collage .row:nth-child(odd) {
      animation: move-left linear 100000ms both alternate 2;
   }
   #collage .row:nth-child(even) {
      animation: move-left linear 100000ms both alternate-reverse 2;
   }  
   #collage .item {
      display: flex;
      overflow: hidden;
      border: 2px solid #fff;
   }
   #collage .item img {
      object-fit: cover;
      opacity: 0;
      transition: opacity 400ms;
      width: 100%;
   }
   @keyframes move-left {
      from {
         transform: translateX(0px) rotate(0.001deg);
      }
      to {
         transform: translateX(-200px) rotate(0.001deg);

      }
   }

   @media (min-width: 481px) {
      .row {
         grid-template-columns: repeat(auto-fill, minmax(22vh, 1fr));
      } 
      .wrapper {
         grid-template-rows: repeat(auto-fill, minmax(20vh, 1fr));
      }
      .fake_wrapper {
         grid-template-columns: repeat(auto-fill, minmax(22vh, 1fr));
         grid-template-rows: repeat(auto-fill, minmax(20vh, 1fr));
      }
      .item {
         border: 2px solid #fff;
      }
   }

   /* @media (max-width: 1279px) {
      .row {
         grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
      } 
      .wrapper {
         grid-template-rows: repeat(auto-fill, minmax(180px, 1fr)) !important;
      }
      .fake_wrapper {
         grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
         grid-template-rows: repeat(auto-fill, minmax(180px, 1fr)) !important;
      }
      .item {
         border: 2px solid #fff;
      }
   } */
/*
   @media (max-width: 767px) {
      .row {
         grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)) !important;
      } 
      .wrapper {
         grid-template-rows: repeat(auto-fill, minmax(150px, 1fr)) !important;
      }
      .fake_wrapper {
         grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)) !important;
         grid-template-rows: repeat(auto-fill, minmax(150px, 1fr)) !important;
      }
      .item {
         border: 2px solid #fff;
      }
   }
   */
   @media (max-width: 480px) {
      .row {
         grid-template-columns: repeat(auto-fill, minmax(16vh, 1fr)) !important;
      } 
      .wrapper {
         grid-template-rows: repeat(auto-fill, minmax(14vh, 1fr)) !important;
      }
      .fake_wrapper {
         grid-template-columns: repeat(auto-fill, minmax(16vh, 1fr)) !important;
         grid-template-rows: repeat(auto-fill, minmax(14vh, 1fr)) !important;
      }
      .item {
         border: 2px solid #fff;
      }
   }
   
   </style>

   <div id="collage">
      <div class="fake_wrapper"></div>
      <div class="wrapper"></div>
   </div>

   <?php

   $out = ob_get_clean(); // pass buffer to variable

   return $out; // shortcode return

});
