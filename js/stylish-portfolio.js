(function ($) {
  "use strict"; // Start of use strict
  // Closes the sidebar menu
  $(".menu-toggle").click(function (e) {
    e.preventDefault();
    $("#sidebar-wrapper").toggleClass("active");
    $(".menu-toggle > .fa-bars, .menu-toggle > .fa-times").toggleClass("fa-bars fa-times");
    $(this).toggleClass("active");
  });
  // Smooth scrolling using jQuery easing
  $('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function () {
    if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
      if (target.length) {
        $('html, body').animate({
          scrollTop: target.offset().top
        }, 1000, "easeInOutExpo");
        return false;
      }
    }
  });
  // Closes responsive menu when a scroll trigger link is clicked
  $('#sidebar-wrapper .js-scroll-trigger').click(function () {
    $("#sidebar-wrapper").removeClass("active");
    $(".menu-toggle").removeClass("active");
    $(".menu-toggle > .fa-bars, .menu-toggle > .fa-times").toggleClass("fa-bars fa-times");
  });
  // Scroll to top button appear
  $(document).scroll(function () {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
      $('.scroll-to-top').fadeIn();
    } else {
      $('.scroll-to-top').fadeOut();
    }
  });
})(jQuery); // End of use strict
// Disable Google Maps scrolling
// See http://stackoverflow.com/a/25904582/1607849
// Disable scroll zooming and bind back the click event
var onMapMouseleaveHandler = function (event) {
  var that = $(this);
  that.on('click', onMapClickHandler);
  that.off('mouseleave', onMapMouseleaveHandler);
  that.find('iframe').css("pointer-events", "none");
}
var onMapClickHandler = function (event) {
  var that = $(this);
  // Disable the click handler until the user leaves the map area
  that.off('click', onMapClickHandler);
  // Enable scrolling zoom
  that.find('iframe').css("pointer-events", "auto");
  // Handle the mouse leave event
  that.on('mouseleave', onMapMouseleaveHandler);
}
// Enable map zooming with mouse scroll when the user clicks the map
$('.map').on('click', onMapClickHandler);
/******************************************************************************************************************** */
/* ADDED  image-gallery from https://bootsnipp.com/snippets/featured/thumbnail-gallery-modal-view-and-key-navigation  */
let modalId = $('#image-gallery');
$(document)
  .ready(function () {
    loadGallery(true, 'a.thumbnail');
    //This function disables buttons when needed
    function disableButtons(counter_max, counter_current) {
      $('#show-previous-image, #show-next-image')
        .show();
      if (counter_max === counter_current) {
        $('#show-next-image')
          .hide();
      } else if (counter_current === 1) {
        $('#show-previous-image')
          .hide();
      }
    }
    /**
     *
     * @param setIDs        Sets IDs when DOM is loaded. If using a PHP counter, set to false.
     * @param setClickAttr  Sets the attribute for the click handler.
     */
    function loadGallery(setIDs, setClickAttr) {
      let current_image,
        selector,
        counter = 0;
      $('#show-next-image, #show-previous-image')
        .click(function () {
          if ($(this)
            .attr('id') === 'show-previous-image') {
            current_image--;
          } else {
            current_image++;
          }
          selector = $('[data-image-id="' + current_image + '"]');
          updateGallery(selector);
        });
      function updateGallery(selector) {
        console.log("updateGallery")
        console.log(selector)
        let $sel = selector;
        current_image = $sel.data('image-id');
        $('#image-gallery-title')
          .text($sel.data('title'));
        $('#image-gallery-image')
          .attr('src', $sel.data('image'));
        disableButtons(counter, $sel.data('image-id'));
      }
      if (setIDs == true) {
        $('[data-image-id]')
          .each(function () {
            counter++;
            $(this)
              .attr('data-image-id', counter);
          });
      }
      $(setClickAttr)
        .on('click', function () {
          updateGallery($(this));
        });
    }
  });
// build key actions
$(document)
  .keydown(function (e) {
    switch (e.which) {
      case 37: // left
        if ((modalId.data('bs.modal') || {})._isShown && $('#show-previous-image').is(":visible")) {
          $('#show-previous-image')
            .click();
        }
        break;
      case 39: // right
        if ((modalId.data('bs.modal') || {})._isShown && $('#show-next-image').is(":visible")) {
          $('#show-next-image')
            .click();
        }
        break;
      default:
        return; // exit this handler for other keys
    }
    e.preventDefault(); // prevent the default action (scroll / move caret)
  });
/*************************************************************** */
/* ADDED  image-gallery from https://www.bootply.com/F5JEzjmilp   BOODSTRAP 3*/
/* activate the carousel */
//$("#modal-carousel").carousel({interval:false});
/* change modal title when slide changes */
/*
$("#modal-carousel").on("slid.bs.carousel", function () {
  $(".modal-title").html($(this).find(".active img").attr("title"));
})
*/
/* when clicking a thumbnail */
/*
$(".row .thumbnail").click(function(){
    var content = $(".carousel-inner");
    var title = $(".modal-title");
 
    content.empty();
    title.empty();
 
  	var id = this.id;
    var repo = $("#img-repo .item");
    var repoCopy = repo.filter("#" + id).clone();
    var active = repoCopy.first();
 
    active.addClass("active");
    title.html(active.find("img").attr("title"));
  	content.append(repoCopy);
    // show the modal
  	$("#modal-gallery").modal("show");
});
*/
/*  Lightbox http://ashleydw.github.io/lightbox  */
function loadPhotoPagetitle (title){
  var titlediv = document.querySelector('#photopage-title');
  titlediv.innerHTML = '<img src="../img/boom.svg" id="boom">' + title +
    '<img src="../img/boom.svg" id="boom">';
};
function loadGallery(configFile, anchor) {
  var photopageSection = document.querySelectorAll('section#photos')[0];
  //console.log(photopageSection)
  if (photopageSection) {
    photopageSection.style.display = "none";
  }
  galleryTitle = configFile.galleryTitle;
  loadPhotoPagetitle ( galleryTitle );
  var imgFilesArray = configFile.imgFilesArray
  //get the template element:
  var thumbnailsTemplate = document.querySelector('template#thumbnail');
  var thumbnailsDiv = document.querySelector('div.container#thumbnails div.row');
  thumbnailsDiv.innerHTML = '';
  //for each item in the array:
  imgFilesArray.forEach(img => {
    var cloneThumbnailsTemplate = document.importNode(thumbnailsTemplate.content,
      true);
    var ahref = cloneThumbnailsTemplate.querySelectorAll('a.thumbnail')[0];
    ahref.setAttribute("href", anchor + "/images/" + img);
    ahref.setAttribute("data-title", galleryTitle);
    var imgTag = cloneThumbnailsTemplate.querySelectorAll('a.thumbnail img')[0];
    imgTag.setAttribute("src", anchor + "/thumbs/" + img);
    thumbnailsDiv.appendChild(cloneThumbnailsTemplate);
    window.scrollTo(0, 0);
  });
}
function sortGalleries(allGalleries) {
  return allGalleries.sort(function(a, b) {
    var nameA = a.name.toLowerCase();
    var nameB = b.name.toLowerCase();
    if (nameA < nameB) return 1;
    if (nameA > nameB) return -1;
    return 0;
  });
}
function loadRecentGalleries(allGalleries) {
  //console.log(allGalleries)
  allGalleries = sortGalleries(allGalleries)
  var galleryLinkTemplate = document.querySelector('template#galleryLink');
  var elemDiv = document.createElement('div');
  elemDiv.setAttribute("class", "row no-gutters");
  allGalleries.forEach(gallery => {
    var clonegalleryLinkTemplate = document.importNode(
      galleryLinkTemplate.content,
      true);
    var ahref = clonegalleryLinkTemplate.querySelectorAll('a.portfolio-item')[0];
    ahref.setAttribute("href", "#" + gallery.dir);
    var h2 = clonegalleryLinkTemplate.querySelectorAll('h2.galleryLink')[0];
    h2.innerHTML = gallery.name
    var img = clonegalleryLinkTemplate.querySelectorAll('img.img-fluid')[0];
    img.setAttribute("src", gallery.dir + "/images/cover.jpg")
    elemDiv.appendChild(clonegalleryLinkTemplate);
  });
  var elemDivContainer = document.createElement('div');
  elemDivContainer.setAttribute("class", "container");
  elemDivContainer.appendChild(elemDiv);
  var elemSection = document.createElement('section');
  elemSection.setAttribute("id", "photos");
  elemSection.setAttribute("class", "content-section");
  elemSection.appendChild(elemDivContainer);
  var photopageDiv = document.querySelector('div#photopage');
  photopageDiv.appendChild(elemSection);
}
function loadAllGalleries(allGalleries) {
  //console.log(allGalleries)
  var galleryBaseDir = "/Pictures/";
  var galleryLinkTemplate = document.querySelector('template#galleryLink');
  var elemDiv = document.createElement('div');
  elemDiv.setAttribute("class", "row no-gutters");
  allGalleries.forEach(gallery => {
    var clonegalleryLinkTemplate = document.importNode(
      galleryLinkTemplate.content,
      true);
    var ahref = clonegalleryLinkTemplate.querySelectorAll('a.portfolio-item')[0];
    ahref.setAttribute("href", "#" + gallery.dir);
    var h2 = clonegalleryLinkTemplate.querySelectorAll('h2.galleryLink')[0];
    h2.innerHTML = gallery.name
    var img = clonegalleryLinkTemplate.querySelectorAll('img.img-fluid')[0];
    img.setAttribute("src", galleryBaseDir + gallery.dir + "/images/cover.jpg")
    elemDiv.appendChild(clonegalleryLinkTemplate);
  });
  var elemDivContainer = document.createElement('div');
  elemDivContainer.setAttribute("class", "container");
  elemDivContainer.appendChild(elemDiv);
  var elemSection = document.createElement('section');
  elemSection.setAttribute("id", "photos");
  elemSection.setAttribute("class", "content-section");
  elemSection.appendChild(elemDivContainer);
  var photopageDiv = document.querySelector('div#photopage');
  photopageDiv.appendChild(elemSection);
}
$(document).on('click', '[data-toggle="lightbox"]', function (event) {
  event.preventDefault();
  $(this).ekkoLightbox();
});
$(document).on('click', 'section#photos.content-section div#photopage a.portfolio-item', function (event) {
  var galleryBaseDir = "/Pictures/";
  event.preventDefault();
  anchor = $(this).attr('href').substr(1);
  var newurl = window.location.protocol + "//" + window.location.host + galleryBaseDir +"#" + anchor;
  document.location.href= newurl;
});
$(document).on('click', 'body.photopage div#photopage a.portfolio-item', function (event) {
  event.preventDefault();
//  console.log($(this).attr('href').substr(1))
  anchor = $(this).attr('href').substr(1);
  jQuery.getJSON(anchor + '/config.json', function (data) {
    loadGallery(data, anchor)
    var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + "#" + anchor;
    var stateObj = {
      anchor: anchor
    };
    window.history.pushState(stateObj, anchor, newurl);
  });
});
$(window).on('popstate', function (e) {
  var state = e.originalEvent.state;
  if (state !== null) {
    if (state.anchor.length > 0) {
      jQuery.getJSON(state.anchor + '/config.json', function (data) {
        loadGallery(data, state.anchor)
      });
    }
  } else {
    var photopageSection = document.querySelectorAll('section#photos')[0];
    if (photopageSection) {
      var thumbnailsDiv = document.querySelector('div.container#thumbnails div.row');
      thumbnailsDiv.innerHTML = '';
      photopageSection.style.display = "block";
      loadPhotoPagetitle ('Foto pagina')
    } else {
      jQuery.getJSON('galleries_config.json', function (allGalleries) {
        loadPhotoPagetitle( 'Foto pagina' )
        var thumbnailsDiv = document.querySelector('div.container#thumbnails div.row');
        thumbnailsDiv.innerHTML = '';
        allGalleries = sortGalleries(allGalleries)
        loadAllGalleries(allGalleries)
      });
    }
  }
});
/********************************************************
 * https://bootsnipp.com/snippets/featured/carousel-extended-320-compatible
 *
 * https://bootsnipp.com/snippets/featured/carousel-extended-320-compatible
 *
 *
 */