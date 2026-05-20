<div class="custon-popout-popup">
  <div class="custon-popout-popup-inner <?php block_field('background-color');?> <?php block_field('degradado-fondo');?> 	<?php block_field('mascara-image') ?>">
    <span class="custon-popout-close"></span>
	  <div class="logo-popout">
	  <img src="<?php block_field('logotipo');?>" alt="">
	  </div>
    <h3 class="<?php block_field('text-color');?>">
      <?php block_field('title'); ?>
    </h3>
	  <p class="<?php block_field('text-color');?>">
	  	<?php block_field('subtitle-text');?>
	  </p>

    <div class="buttom_url <?php block_field('mostrar-boton');?> "><a class="<?php block_field('color-boton');?> " href="<?php block_field('botton-popout'); ?>">
      <?php block_field('text-url'); ?>
      </a></div>
  </div>
</div>
<style>
/*text color*/
.black-text {color:#292929;}
.white-text {color:#ffffff;}
.gray-text {color:#3c3c3b;}
	
/*background color*/
.negro {background-color:#292929;}
.gris {background-color:#3c3c3b;}
.verde {background-color:#5bb65f;}
.rojo {background-color:#e86848;}
.azul {background-color:#55c5e5;}
.ocre {background-color:#FFA661;}
.white {background-color: #ffffff; color:#292929 !important}

.custon-popout-popup {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0,0,0,0.7);
    z-index: 1111111;
    visibility: hidden;
    opacity: 0;
    transition: all .2s ease-in;
}
.custon-popout-popup-inner {
    width: 80vw;
    height: 80vh;
	max-width: 1024px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translateY(-50%) translateX(-50%);
    padding: 30px;
    border-radius:10px;
    background-size: cover;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-flow: wrap;
    overflow: hidden;
    min-height: 490px;
	max-height: 600px;
}
	
.custon-popout-popup-inner:before,.custon-popout-popup-inner.Yes:after {
    content:'';
    position: absolute; 
    z-index: 1;
    width: 70%;padding-bottom: 70%;
    top: 20px;
    right: 2vw;
}
.pentagono:before,.pentagono:after {-webkit-clip-path: polygon(50% 0, 100% 34%, 100% 100%, 0 100%, 0 34%);
    clip-path: polygon(50% 0, 100% 34%, 100% 100%, 0 100%, 0 34%);
}
.semicirculo:before,.semicirculo:after{border-radius:50% 50% 0 0;}

.custon-popout-popup-inner:before {
	z-index: 1;
    background: no-repeat scroll  url('https://www.madrural.com/wp-content/uploads/2022/06/fondo_madfest.jpg') center;
    background-size: cover;
}
.custon-popout-popup-inner.Yes:after { 
z-index: 5;
background: rgb(41,41,41);
background: radial-gradient(circle, rgba(41,41,41,0) 0%, rgba(0,0,0,1) 100%);
}		
.custon-popout-close { 
position: absolute;top: 10px;right: 10px;cursor: pointer;width: 40px;height: 40px;font-size: 21px;line-height: 1;display: flex;justify-content: center;align-items: center;z-index: 100;}
.custon-popout-close:before,.custon-popout-close:after {content: ''; width: 100%; height: 2px;position: absolute;background: #ffffff;}
.custon-popout-close:before {transform: rotate(45deg)}
.custon-popout-close:after {transform: rotate(-45deg)}
.custon-popout-popup.open { visibility: visible; opacity: 1;}
.custon-popout-popup-inner .buttom_url {align-self: flex-end; width: 100%;justify-content: center;padding: 2rem;display:none; border-radius:10px;} 
.logo-popout {width: 24%; min-width: 135px;z-index: 10;align-self: flex-start;}
.logo-popout img {width: 100%}
.custon-popout-popup-inner h3 {width: 64%;text-align: center;font-size: 4.5rem;font-weight: 900;z-index: 10;-ms-word-wrap: normal;-ms-word-break: normal;word-wrap: normal;word-break: normal;}
.custon-popout-popup-inner p {width: 20%;text-align: center;font-size: 1.8rem;z-index: 10;-ms-word-wrap: normal;-ms-word-break: normal;word-wrap: normal;word-break: normal;align-self: flex-start;min-width: 200px;width: 30%;}
.custon-popout-popup-inner .buttom_url.Yes {display: flex;z-index: 10;}
.custon-popout-popup-inner .buttom_url a {color: #fff; text-transform: uppercase;padding: 15px 2vw;} 
.custon-popout-popup-inner .buttom_url a:hover {text-decoration: none}
	
@media (max-width: 768px){
    #pop-up {top: 0;left: 0; width: 100%; margin: 0; bottom: 0; overflow-y: scroll; }
    .custon-popout-popup-inner h3 {font-size: 4rem; width: 100%} 
    .custon-popout-popup-inner p {margin: 0 auto}
    .custon-popout-popup-inner:before,.custon-popout-popup-inner.Yes:after {width: 100vw; padding-bottom: 100vw;top: unset; right: -9vw;bottom: 0;}
    .custon-popout-popup-inner {padding: 10px}
    .custon-popout-popup-inner h3 {font-size: 2rem} 
}
@media (max-width: 700px) and (orientation: landscape) {
    .logo-popout img {max-width: 100px}
    .custon-popout-popup-inner{min-height: unset}
    .custon-popout-popup-inner p {margin: 0 !important; width: 100%}
    .custon-popout-popup-inner:before {width: 100wv;top: 10px}
} 
</style>
<script>
	window.addEventListener( 'load', function() {
    
    //Evento para que aparezca el pop up a los 120 segundos. Puedes poner el tiempo que quieras pero ten en cuenta que el valor es en milisegundos
    setTimeout( function() {
        const popup = document.querySelector( '.custon-popout-popup' );
        popup.classList.add( 'open' ); 
    }, 0 );


    //Cerrar el pop up al pulsar en la aspa
    const btnClosePopup = document.querySelector( '.custon-popout-close' );
    if ( btnClosePopup ) {
        btnClosePopup.addEventListener( 'click', () => {
        const popup = document.querySelector( '.custon-popout-popup' );               
            popup.classList.remove( 'open' );
        });
    }

}); 
</script>