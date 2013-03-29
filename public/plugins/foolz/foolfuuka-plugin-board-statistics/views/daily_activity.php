<?php
if ( ! defined('DOCROOT'))
{
	exit('No direct script access allowed');
}

$data_array = json_decode($data);

$temp = [];

foreach ($data_array as $k => $t)
{
	foreach ($t as $kS => $tS)
	{
		$temp[$k][] = [
			'group' => 'posts',
			'time' => $tS->time,
			'count' => $tS->posts
		];
		$temp[$k][] = [
			'group' => 'images',
			'time' => $tS->time,
			'count' => $tS->images
		];
		$temp[$k][] = [
			'group' => 'sage',
			'time' => $tS->time,
			'count' => $tS->sage
		];
	}
}
?>

<div id="graphs"></div>

<script>
// d3.js
var m = [20, 30, 30, 40],
	w = 960 - m[3] - m[1],
	h = 500 - m[0] - m[2];
var x = d3.time.scale().range([0, w]);
var y = d3.scale.linear().range([h, 0]);
var z = d3.scale.category20c();

var color = d3.scale.ordinal()
	.range(["#008000", "#0000ff", "#ff0000"]);
var xAxis = d3.svg.axis()
	.scale(x)
	.orient("bottom")
	.ticks(d3.time.hours);
var yAxis = d3.svg.axis()
	.scale(y)
	.orient("left");
var stack = d3.layout.stack()
	.offset("zero")
	.values(function(d) { return d.values; })
	.x(function(d) { return d.time; })
	.y(function(d) { return d.count; })
	.order("reverse");
var nest = d3.nest()
	.key(function(d) { return d.group});
var area = d3.svg.area()
	.interpolate("basis")
	.x(function(d) { return x(d.time); })
	.y0(function(d) { return y(d.y0); })
	.y1(function(d) { return y(d.y); });

// data
var data_board = <?= json_encode($temp['board']) ?>;
var data_ghost = <?= json_encode($temp['ghost']) ?>;
var data_karma = <?= json_encode($temp['karma']) ?>;

data_board.forEach(function(d) {
	d.time = new Date(d.time * 1000);
	d.count = +d.count;
});

data_ghost.forEach(function(d) {
	d.time = new Date(d.time * 1000);
	d.count = +d.count;
});

data_karma.forEach(function(d) {
	d.time = new Date(d.time * 1000);
	d.count = +d.count;
});

// graph
var svg_board = d3.select("#graphs").append("svg")
	.attr("width", w + m[3] + m[1])
	.attr("height", h + m[0] + m[2])
	.append("g")
	.attr("transform", "translate(" + m[3] + "," + m[0] + ")");

var layers = stack(nest.entries(data_board));
x.domain(d3.extent(data_board, function(d) { return d.time; }));
y.domain([0, d3.max(data_board, function(d) { return d.y + d.y0 + 2; })]).range([h, 0]);

	svg_board.selectAll(".layer")
		.data(layers)
		.enter().append("path")
		.attr("class", "layer")
		.attr("d", function(d) { return area(d.values); })
		.style("fill", function(d, i) { return color(i); });

	svg_board.append("g")
		.attr("class", "x axis")
		.attr("transform", "translate(0," + h + ")")
		.call(xAxis);

	svg_board.append("g")
		.attr("class", "y axis")
		.call(yAxis);

	svg_board.append("text")
		.attr("x", 10)
		.attr("dy", 20)
		.text("Activity (Board)")
		.style("font-weight", "bold");

var svg_ghost = d3.select("#graphs").append("svg")
	.attr("width", w + m[3] + m[1])
	.attr("height", h + m[0] + m[2])
	.append("g")
	.attr("transform", "translate(" + m[3] + "," + m[0] + ")");

var layers = stack(nest.entries(data_ghost));
x.domain(d3.extent(data_ghost, function(d) { return d.time; }));
y.domain([0, d3.max(data_ghost, function(d) { return d.y + d.y0 + 2; })]).range([h, 0]);

	svg_ghost.selectAll(".layer")
		.data(layers)
		.enter().append("path")
		.attr("class", "layer")
		.attr("d", function(d) { return area(d.values); })
		.style("fill", function(d, i) { return color(i); });

	svg_ghost.append("g")
		.attr("class", "x axis")
		.attr("transform", "translate(0," + h + ")")
		.call(xAxis);

	svg_ghost.append("g")
		.attr("class", "y axis")
		.call(yAxis);

	svg_ghost.append("text")
		.attr("x", 10)
		.attr("dy", 20)
		.text("Activity (Ghost)")
		.style("font-weight", "bold");

var svg_karma = d3.select("#graphs").append("svg")
	.attr("width", w + m[3] + m[1])
	.attr("height", h + m[0] + m[2])
	.append("g")
	.attr("transform", "translate(" + m[3] + "," + m[0] + ")");

var layers = stack(nest.entries(data_karma));
x.domain(d3.extent(data_karma, function(d) { return d.time; }));
y.domain([0, d3.max(data_karma, function(d) { return d.y + d.y0 + 2; })]);

	svg_karma.selectAll(".layer")
		.data(layers)
		.enter().append("path")
		.attr("class", "layer")
		.attr("d", function(d) { return area(d.values); })
		.style("fill", function(d, i) { return color(i); });

	svg_karma.append("g")
		.attr("class", "x axis")
		.attr("transform", "translate(0," + h + ")")
		.call(d3.svg.axis().scale(x).orient("bottom").ticks(d3.time.days));

	svg_karma.append("g")
		.attr("class", "y axis")
		.call(yAxis);

	svg_karma.append("text")
		.attr("x", 10)
		.attr("dy", 20)
		.text("Karma")
		.style("font-weight", "bold");

// graph legend
d3.selectAll("svg").each(function(d) {
	var e = d3.select(this);

	e.append("rect")
		.attr("x", 830)
		.attr("y", 15)
		.attr("width", 25).attr("height", 15)
		.style("fill", color(1));

	e.append("text")
		.attr("x", 860)
		.attr("y", 27.5)
		.text("Images");

	e.append("rect")
		.attr("x", 830)
		.attr("y", 40)
		.attr("width", 25).attr("height", 15)
		.style("fill", color(0));

	e.append("text")
		.attr("x", 860)
		.attr("dy", 52.5)
		.text("Posts");

	e.append("rect")
		.attr("x", 830)
		.attr("y", 65)
		.attr("width", 25).attr("height", 15)
		.style("fill", color(2));

	e.append("text")
		.attr("x", 860)
		.attr("dy", 77.5)
		.text("Sages");
});
</script>