<header class="hero container-fluid">
    <div class="hero__content container mx-auto">
        <!-- I'm putting the nav inside this "fixed-nav-container" to fix some issues happens on scroll -->
        @includeIf('cms::frontend.layouts.navbar')
           <!------------------------------>
    <!--Hero---------------->
    <!------------------------------>
        <div class="hero__body col-lg-7 px-0">
            <h1 class="hero__title text-center mb-4">
                 Sistema de administración de XCMG Libra International
            </h1>
            <div class=" text-center mx-0 mb-4">
                Aplicación de gestión de POS, ecommerce, inventario, servicios y otros.
            </div>
           
        </div>
    </div>
    <div class="hero__row d-block d-lg-flex row">
        <div class="hero__empty-column col-lg-7"></div>
        @php
            $bg_img_url = asset('modules/cms/img/home.png');
            if(!empty($page->feature_image_url)) {
                $bg_img_url = $page->feature_image_url;
            }
        @endphp
        <div class="hero__image-column col-lg-5" 
            style="background-image: url({{$bg_img_url}});">
        </div>
    </div>
</header>