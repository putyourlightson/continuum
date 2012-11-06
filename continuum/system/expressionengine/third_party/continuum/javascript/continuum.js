var continuum_delay = 2000;
var last_log_id = 0;
var pending_log_ids = '';


$(document).ready(function() 
{
	$(".chzn-select").chosen();
	
	
	$(".continuum_filters input.refresh_button").click(function() 
	{
		window.location = window.location;
	});
	
	
	$(".continuum_filters select").change(function() 
	{
		window.location = $(".continuum_filters input#base_url").val() + "&" + $(this).attr("name") + "=" + $(this).val();
	});
	
	
	last_log_id = $(".continuum_filters input#last_log_id").val();
	pending_log_ids = $(".continuum_filters input#pending_log_ids").val();
	
	setTimeout("update_log(" + last_log_id + ", '" + pending_log_ids + "')", continuum_delay);
	
	
	$(".visualise_buttons input.visualise").click(function() 
	{
		$("#d3").html("");
		
		var nodes = [];
		$($("table.mainTable tbody tr").get().reverse()).each(function() {
			var name = $(this).find("td:nth-child(3) a").text();
			var type = $(this).find("td:nth-child(2) span").attr("class");	
			
			var node = nodes[name] ? nodes[name] : {name: name};			
			node.count = 1; //node.count ? (node.count + 1) : 1;
			
			if (!node.type || type != "action visit") {
				node.type = type;
			}
			
			nodes[name] = node;
		});
		
		
		var links = [];
		var prev_link = '';
		$($("table.mainTable tbody tr").get().reverse()).each(function() {
			var name = $(this).find("td:nth-child(3) a").text();			
			if (prev_link && name != prev_link) {
				links.unshift({source: prev_link, target: name});
			}			
			prev_link = name;
		});
		
		var w = 1000;
		w = ($("#continuum").width() < w) ? $("#continuum").width() : w;
		continuum_d3(w, 300, nodes, links);
		
		return false;
	});
	
	$(".visualise_buttons input.clear").click(function() 
	{
		$("#d3").html("");
		return false;
	});
});


function update_log(last_log_id, pending_log_ids)
{
	var ajax_url = $(".continuum_filters input#ajax_url").val() + "&last_log_id=" + last_log_id + "&pending_log_ids=" + pending_log_ids;
	
	$.getJSON(ajax_url, function(data) 
	{
		$(data.logs).each(function(i, log)
		{
			var zebra_class = $("table.mainTable tbody tr:first").hasClass("odd") ? "even" : "odd";
			$("table.mainTable tbody").prepend('<tr class="' + zebra_class + ' continuum_fade" style="display: none;"><td>' + log.join('</td><td>') + '</td></tr>');
		});
		
		$(data.pending_logs).each(function(i, log)
		{
			$("table.mainTable tbody span#log_" + log[0]).html(log[1]);
		});
		
		$("table.mainTable tbody tr.continuum_fade").fadeIn(2000);
		
		var limit = parseInt($(".continuum_filters select[name=limit]").val());
		var total = $("table.mainTable tbody tr").length;
		if (total > limit)
		{
			for (var i = limit; i < total; i++)
			{
				$("table.mainTable tbody tr:nth-child(" + (i + 1) + ")").remove();
			}
			
			total = limit;
		}
		
		// update log counts
		$("span.log_count").html(total);
		$("span.total_logs").html(parseInt($("span.total_logs").html()) + data.logs.length);
		
		setTimeout("update_log(" + data.last_log_id + ", '" + data.pending_log_ids + "')", continuum_delay);
	});
}