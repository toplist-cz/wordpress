jQuery(document).ready(function($){
  
  var closed = $("#toplist_cz_dashboard").hasClass("closed");
  var inside = $("#toplist_cz_dashboard .inside");
  var content = inside.find(".content");
  var spinner = inside.find(".spinner");
  var erroricon = inside.find(".erroricon");

  var active = true;
  var ok_reload_time    = 15 * 60 * 1000;  // 15 minutes reload time
  var error_reload_time =  1 * 60 * 1000;  // 1 minutes reload time if error encountered
  var reload_time = ok_reload_time;
  var previous_reload_was_error = false;
  var timer_id = setInterval(reload, reload_time);
  
  var initial_error = false;
  
  function reload() {
    if (!active && timer_id) {
      clearInterval(timer_id);
      timer_id = 0;
      return;
    }

    var output = "";
    var errmsg;
    spinner.show();
    erroricon.hide();

    var initial_fetch = (content.text().trim() == "");

    $.post(ajaxurl, data, function(response) {
      if (initial_fetch && !closed) inside.hide();
      if (spinner.hasClass("reload"))
        spinner.fadeOut();
      else
        spinner.hide();

      if (response.success && previous_reload_was_error) {
        previous_reload_was_error = false;
        reload_time = ok_reload_time;
        if (timer_id) clearInterval(timer_id);
        timer_id = setInterval(reload, reload_time);
      }

      if (!response.success && response.hasOwnProperty("reload") && response.reload == true) {
        errmsg = "Došlo k chybě při načítání. Zkusím to za chvíli znova.";
        if (response.html.trim() != "")
          errmsg += "<br />" + response.html.trim();
        if (!(initial_fetch || initial_error))
          erroricon.show().attr("title", errmsg.replace("<br />", "\n").replace("načítání", "obnovování"));
        if (!previous_reload_was_error) {
          if (initial_fetch) {
            initial_error = true;
            output = errmsg;
          }
          previous_reload_was_error = true;
          reload_time = error_reload_time;
          if (timer_id) clearInterval(timer_id);
          timer_id = setInterval(reload, reload_time);
          spinner.addClass("reload");
        } else {
          return;
        }
      } else {
        output = response.html;
        if (!response.success) {
          if (timer_id) clearInterval(timer_id);
          timer_id = 0;
        }
        spinner.addClass("reload");
      }

      if (response.success && initial_error) {
        if (!closed) inside.slideUp('fast').promise();
        initial_error = false;
        initial_fetch = true;
      }
      if (output != "") {
        content.html(output);
        if (initial_fetch && !closed) inside.slideDown('fast', function() { inside.removeAttr("style"); });
      }

      if (!response.success && initial_fetch) {
        inside.addClass("error");
        return;
      }
      initial_fetch = false;
      inside.removeClass("error");
      draw_graphs();
    })
    .fail(function() {
      if (initial_fetch && !closed) inside.hide();
      inside.find(".spinner").hide();
      content.html("fail");
      inside.addClass("error");
      if (initial_fetch && !closed) inside.slideDown('fast', function() { inside.removeAttr("style"); });
      return;
    });
  }
  
  function array2flot(arr, jitter) {
    jitter = jitter || 0;
    var data = [];
    for (var key in arr) {
      data.push([parseInt(key) + jitter, parseInt(arr[key])]);
    }
    return data;
  }
  
  function flot_navstevy_graph(data, selector, barWidth) {
    barWidth = barWidth || 0.35;
    var dataSets = [{
            data: array2flot(data["navstevy"], -barWidth),
            label: "Návštěvy",
            color: "#994c4c"
          }, {
            data: array2flot(data["zhlednuti"]),
            label: "Zhlédnutí",
            color: "#404080"
          }];
    var options = {
          bars: {
            show: true,
            lineWidth: 1,
            barWidth: barWidth
          },
          xaxis: {
            ticks: Object.keys(data["navstevy"]).length,
            minTickSize: 1,
            tickDecimals: 0,
            tickLength:0
          }, 
          grid: {
            hoverable: true,
            borderWidth: 1,
            borderColor: "LightGray"
          },
          legend: {
            labelFormatter: function(label, series) {
              return label + ' (' + series.data.reduce(function(pv, cv) { return pv + cv[1]; }, 0).toLocaleString() + ')';
            }
          }
    }
    
    var plot = $.plot(selector, dataSets, options);
    
/* this should add labels on top of bars 
    var ctx = plot.getCanvas().getContext("2d"); // get the context
    var ddata = plot.getData()[0].data;  // get your series data
    var xaxis = plot.getXAxes()[0]; // xAxis
    var yaxis = plot.getYAxes()[0]; // yAxis
    var offset = plot.getPlotOffset(); // plots offset
    ctx.font = "16px 'Arial'"; // set a pretty label font
    ctx.fillStyle = "black";
    for (var i = 0; i < ddata.length; i++){
        var text = ddata[i][1] + '';
        var metrics = ctx.measureText(text);
        console.log(metrics.width);
        var xPos = (xaxis.p2c(ddata[i][0])+offset.left) - metrics.width/2; // place it in the middle of the bar
        var yPos = yaxis.p2c(ddata[i][2]) + offset.top - 5; // place at top of bar, slightly up
        ctx.fillText(text, xPos, yPos);
    }
*/
    selector.attr("data", JSON.stringify(data));
  }

  function showTooltip(x, y, contents, z) {
      $('<div id="flot-tooltip">' + contents + '</div>').css({
          top: y - 20,
          left: x < 100 ? x + 25 : x - 95,
          'border-color': z
      }).appendTo("body").show();
  }

  $('body').on('mouseenter', '#toplist_cz_dashboard .inside table tr td', function (event, pos, item) {
    var $this = $(this);
    if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
      $this.attr('title', $this.text());
    }
  });

  $('body').on('plothover', '#toplist_cz_dashboard .inside div.graph', function (event, pos, item) {
    if (item) {
      if (previousPoint != item.datapoint) {
        previousPoint = item.datapoint;
        $("#flot-tooltip").remove();

        var data = JSON.parse($(this).attr("data"));
        var index = Math.round(item.datapoint[0]);
        var caption = index;
        if ($(this).parent().attr("id").indexOf("za-den") >= 0)
          caption = index + "<sup>00</sup> &ndash; " + (index+1) + "<sup>00</sup>";
        else if ($(this).parent().attr("id").indexOf("za-mesic") >= 0) {
          var sdata = JSON.parse(window.atob($("#toplist_cz_dashboard #toplist_stats").attr("value")));
          caption = index + ". " + sdata.navstevy_za_mesic.mesic.toLowerCase();
        }
        showTooltip(item.pageX, item.pageY,
            "<strong>" + caption + "</strong>"
            + "<br />Návštěvy: <strong>" + data.navstevy[index] + "</strong>"
            + "<br />Zhlédnutí: <strong>" + data.zhlednuti[index] + "</strong>"
            );
      }
    } else {
      $("#flot-tooltip").remove();
      previousPoint = null;
    }
  });
/*
  function table_2_columns(data, selector, rows) {
    rows = rows || 5;
    var tbody = "";
    for (var key in data) {
      tbody = tbody + "<tr><td>" + data[key] + "</td><td>" + key + "</td></tr>";
      if (--rows == 0) break;
    }
    selector.find("tbody").html(tbody);
  }
*/

  var data = {
    'action'   : 'toplist_cz_dashboard_content',
    '_wpnonce' : $("#toplist_cz_dashboard .inside #toplist_nonce").attr("value")
  };

  function draw_graphs() {
    if (!$('#toplist_cz_dashboard').hasClass("closed")) {
      var data = JSON.parse(window.atob($("#toplist_cz_dashboard #toplist_stats").attr("value")));
      flot_navstevy_graph(data.navstevy_za_den, $("#navstevy-za-den .graph"));
      flot_navstevy_graph(data.navstevy_za_mesic, $("#navstevy-za-mesic .graph"));
      //table_2_columns(response.vstupni_stranky, $("#vstupni-stranky table"));
      //table_2_columns(response.domeny, $("#navstevy-podle-domen table"));
    }
  }
  
  $(window).resize(draw_graphs);
  $('#toplist_cz_dashboard .handlediv').click(draw_graphs);

  $('body').on('click', '#toplist_cz_dashboard #toplist_password_form #toplist_password_submit', function() {
    inside.removeClass("toplist_error_pulsate");
    var pwd = $(this).parents("form").find("#toplist_password");
    if (pwd.val() == '') {
      pwd.css('background-color', 'LightPink');
      alert('Please fill-in the password.');
      return;
    }
    $("#toplist_cz_dashboard .inside #toplist_password_form .spinner").css("display", "inline-block");
    var data = {
      'action'   : 'toplist_cz_save_password',
      '_wpnonce' : $(this).parents("form").find("#toplist_password_nonce").val(),
      'password' : pwd.val()
    };
    $("form#toplist_password_form input").attr("disabled", true);
    $.post(ajaxurl, data, function(response) {
      if (response.success) {
        inside.removeClass("error");
        inside.slideUp('fast');
        content.html(response.html);
        inside.slideDown('fast');
        inside.find(".spinner").hide().addClass("reload");
        inside.find(".erroricon").hide();
        initial_fetch = false;
        draw_graphs();
        reload_time = ok_reload_time;
        if (timer_id) clearInterval(timer_id);
        timer_id = setInterval(reload, reload_time);
      } else {
        inside.html(response.html);
        inside.addClass("toplist_error_pulsate");
      }
    })
    .fail(function() {
      inside.html("fail");
      inside.addClass("toplist_error_pulsate");
    });    

  });

  if (content.text().trim() == "") {  // content not in the widget (not retrieved from cache), we need to get it via ajax
    reload();
  } else {
    spinner.addClass("reload");
    draw_graphs();
  }

  // browser tab visibility:

  var hidden = "hidden";
  if (hidden in document)
    document.addEventListener("visibilitychange", onvischange);
  else if ((hidden = "mozHidden") in document)
    document.addEventListener("mozvisibilitychange", onvischange);
  else if ((hidden = "webkitHidden") in document)
    document.addEventListener("webkitvisibilitychange", onvischange);
  else if ((hidden = "msHidden") in document)
    document.addEventListener("msvisibilitychange", onvischange);
  // IE 9 and lower:
  else if ("onfocusin" in document)
    document.onfocusin = document.onfocusout = onvischange;
  // All others:
  else
    window.onpageshow = window.onpagehide
    = window.onfocus = window.onblur = onvischange;

  function onvischange (evt) {
    var v = "visible", h = "hidden",
        evtMap = {
          focus:v, focusin:v, pageshow:v, blur:h, focusout:h, pagehide:h
        };

    evt = evt || window.event;
    var vis;
    if (evt.type in evtMap)
      vis = evtMap[evt.type];
    else
      vis = this[hidden] ? "hidden" : "visible";

    if (vis == "hidden") {
      active = false;
    } else {
      active = true;
      if (!timer_id) {
        timer_id = setInterval(reload, reload_time);
        reload();
      }
    }
  }

  // set the initial state (but only if browser supports the Page Visibility API)
  if( document[hidden] !== undefined )
    onvischange({type: document[hidden] ? "blur" : "focus"});
});