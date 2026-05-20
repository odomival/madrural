/**
 * Main Javascript.
 * This file is for who want to make this theme as a new parent theme and you are ready to code your js here.
 */
jQuery(document).ready(function ($) {

  //BRUJULA
  $(".brujula_btn li:nth-child(4n-3)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-120deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#5bb65f"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#5bb65f",
      opacity: ".5"
    });
  });
  $(".brujula_btn li:nth-child(4n-2)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(90deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#E86848"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#E86848",
      opacity: ".5"
    });
  });

  $(".brujula_btn li:nth-child(4n-1)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-45deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#55C5E5"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#55C5E5",
      opacity: ".5"
    });
  });
  $(".brujula_btn li:nth-child(4n)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-90deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#FFA661"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#FFA661",
      opacity: ".5"
    });
  });


  //BRUJULA 2
  $(".brujula_btn_2 .e-child:nth-child(1)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-120deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#E86848"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#E86848",
      opacity: ".5"
    });
  });
  $(".brujula_btn_2 .e-child:nth-child(2)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(90deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#5bb65f"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#5bb65f",
      opacity: ".5"
    });
  });

  $(".brujula_btn_2 .e-child:nth-child(3)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-45deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#55C5E5"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#55C5E5",
      opacity: ".5"
    });
  });
  $(".brujula_btn_2 .e-child:nth-child(4)").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-90deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#FFA661"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#FFA661",
      opacity: ".5"
    });
  });	
	
	
  //BRUJULA NEW HOME
  $(".brujula_btn_new #btn_oeste").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-120deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#E86848"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#E86848",
      opacity: ".5"
    });
  });
  $(".brujula_btn_new #btn_vegas").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(90deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#5bb65f"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#5bb65f",
      opacity: ".5"
    });
  });

  $(".brujula_btn_new #btn_norte").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-45deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#55c5e5"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#55c5e5",
      opacity: ".5"
    });
  });
  $(".brujula_btn_new #btn_guada").mouseover(function () {
    $('.svg-container svg').css({
      transform: "rotate(-90deg)"
    });
    $('.svg-container svg circle').css({
      fill: "#FFA661"
    });
    $('.svg-container svg path,.svg-container svg polygon').css({
      fill: "#FFA661",
      opacity: ".5"
    });
  });	

  //Fadein Scroll
  $(window).scroll(function () {
    var windowBottom = $(this).scrollTop() + $(this).innerHeight();
    $(".left-to-right, .right-to-left").each(function () {
      /* Check the location of each desired element */
      var objectBottom = $(this).offset().top + $(this).outerHeight();

      /* If the element is completely within bounds of the window, fade it in */
      if (objectBottom < windowBottom) { //object comes into view (scrolling down)
        if ($(this).css("opacity") == 0) {
          $(this).addClass('aparece');
          $(this).fadeTo(300, 1);
        }
      } else {
        if ($(this).css("opacity") == 1) {
          $(this).removeClass('aparece');
          $(this).fadeTo(300, 0);
        }
      }
    });
  });


  //Scroll suave ancla
  $('a[href^="#"]').click(function () {
    var destino = $(this.hash);
    if (destino.length == 0) {
      destino = $('a[name="' + this.hash.substr(1) + '"]');
    }
    if (destino.length == 0) {
      destino = $('html');
    }
    $('html, body').animate({
      scrollTop: destino.offset().top
    }, 800);
    return false;
  });

  //Sello rotate scroll
  var bodyHeight = $("body").height() - $(window).height();
  window.onscroll = function () {

    //Determine the amount to rotate by.
    var deg = +window.scrollY * (360 / bodyHeight);

    $(".sello-madrural").css({
      "transform": "rotate(" + deg + "deg)",
    });

  };
  //listado concurso

  ////Función para poner los párrafos en negrita
  function resaltar() {
    $('.menu_btn a:contains("¡Participa!")').addClass("resaltar");
  }
  //Cuando la página esté cargada ejecutará la función resaltar
  $(document).ready(resaltar);

  $(window).on("load", resaltar);
	
////Función para poner el menu en negrita
  function resaltar() {
    $('.menu_btn a:contains("Eventos")').addClass("resaltar");
  }
  //Cuando la página esté cargada ejecutará la función resaltar
  $(document).ready(resaltar);

  $(window).on("load", resaltar);
	
//BOTÓN LOAD MORE
	
$( ".jkit-block-loadmore a" ).click(function (event) {
                event.preventDefault();
    });

});
