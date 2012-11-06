function continuum_d3(w, h, nodes, links)
{	
	// add node objects to links
	links.forEach(function(link) {
		link.source = nodes[link.source];
		link.target = nodes[link.target];
	});
	
	var force = d3.layout.force()
		.nodes(d3.values(nodes))
		.links(links)
		.size([w, h])
		.linkDistance(120)
		.charge(-300)
		.on("tick", tick)
		.start();
		
	console.log(force.nodes());	
	console.log(force.links());	
	
	var svg = d3.select("#d3").append("svg:svg")
		.attr("width", w)
		.attr("height", h);
		
	svg.append("svg:defs").selectAll("marker")
		.data(["arrow"])
		.enter().append("svg:marker")
		.attr("id", String)
		.attr("viewBox", "0 -5 10 10")
		.attr("refX", 18)
		.attr("refY", -1.5)
		.attr("markerWidth", 6)
		.attr("markerHeight", 6)
		.attr("orient", "auto")
		.append("svg:path")
		.attr("d", "M0,-5L10,0L0,5");
	
	var path = svg.append("svg:g").attr("class", "lines").selectAll("path")
		.data(force.links())
		.enter().append("svg:path")
		.attr("class", function(d) { return "link"; })
		.attr("marker-end", function(d) { return "url(#arrow)"; });
	
	var circle = svg.append("svg:g").attr("class", "circles").selectAll("circle")
		.data(force.nodes())
		.enter().append("svg:circle")
		.attr("r", function(d) { return (7 + d.count); })
		.attr("class", function(d) { return d.type; })
		.call(force.drag);
	
	var text = svg.append("svg:g").attr("class", "labels").selectAll("g")
	 	.data(force.nodes())
		.enter().append("svg:g");
	
	text.append("svg:text")
		.attr("x", function(d) { return (10 + d.count); })
		.attr("y", ".31em")
		.text(function(d) { return d.name; });
		
		
	function tick() {
		path.attr("d", function(d) {
		var dx = d.target.x - d.source.x,
			dy = d.target.y - d.source.y,
			dr = Math.sqrt(dx * dx + dy * dy);
		return "M" + d.source.x + "," + d.source.y + "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y;
		});
	
		circle.attr("transform", function(d) {
		return "translate(" + d.x + "," + d.y + ")";
		});
	
		text.attr("transform", function(d) {
		return "translate(" + d.x + "," + d.y + ")";
		});
	}
}