$(function() {
    if (screen.width <= 992) {
		$('.sidebar-btn').click(function() {
	        $('.sidebar__link').fadeIn(1)
	        $('.sidebar__icon').fadeIn(1)
	        $('.sidebar-bg').fadeIn(1)
	        $('.wrap').addClass('wrap-open')
		})
		$('.sidebar-bg').click(function() {
			$('.sidebar__link').fadeOut(1)
	        $('.sidebar__icon').fadeOut(1)
	        $('.sidebar__list').fadeOut(1)
	        $('.sidebar-bg').fadeOut(1)
	        $('.wrap').removeClass('wrap-open')
	        $(".sidebar__content").removeClass("sidebar__content_active");
	        $(".sidebar__arrow i").removeClass('arrow-rotate');
		})
    } else {
		$('.sidebar-btn').click(function() {
	        $('.sidebar__link').fadeToggle(1)
	        $('.wrap').toggleClass('wrap-open')
	        $('.sidebar__list').fadeOut(1)
	        $(".sidebar__content").removeClass("sidebar__content_active");
	        $(".sidebar__arrow i").removeClass('arrow-rotate');
		})
    }

	$('.sidebar__content').click(function() {
		$('.sidebar__subitem-wrap').removeClass('sidebar__subitem-wrap_active')
		$('.sidebar__secondary').slideUp(1)
	})


    $(".sidebar__body .sidebar__content").click(function() {
        $(".sidebar__content").removeClass("sidebar__content_active");         
        $(this).addClass("sidebar__content_active");
        $(".sidebar__content_active .sidebar__arrow i").toggleClass('arrow-rotate'); 
        $(this).siblings(".sidebar__list").slideToggle()
        $(this).addClass("sidebar__content_open");
	})

	$('.sidebar__icon').click(function() {    
        if ($('.wrap').hasClass('wrap-open')) {
        } else {
        	$('.sidebar__link').fadeIn(1)
        	 $('.wrap').addClass('wrap-open')
        }
	})

	$(".sidebar__body .sidebar__subitem .sidebar__subitem-wrap").click(function() {
        $(".sidebar__subitem-wrap").removeClass("sidebar__subitem-wrap_active");         
        $(this).addClass("sidebar__subitem-wrap_active");
        $(".sidebar__subitem-wrap_active .sidebar__arrow i").toggleClass('arrow-rotate'); 
        $(this).siblings(".sidebar__list").slideToggle()
	})

});