<?php
$data_array = json_decode($data);

$temp = [];

foreach ($data_array as $k => $t) {
    $timestamp = $t->time;

    if ($this->radix->archive) {
        $datetime = new \DateTime(date('Y-m-d H:i:s', $timestamp), new \DateTimeZone('America/New_York'));
        $datetime->setTimezone(new \DateTimeZone('UTC'));
        $timestamp = strtotime($datetime->format('Y-m-d H:i:s'));
    }

    $temp[] = [
        'group' => 'anons',
        'time' => $timestamp,
        'count' => $t->anons
    ];
    $temp[] = [
        'group' => 'names',
        'time' => $timestamp,
        'count' => $t->names
    ];
    $temp[] = [
        'group' => 'trips',
        'time' => $timestamp,
        'count' => $t->trips
    ];
}

// manually truncate results
array_pop($temp);
array_pop($temp);
array_pop($temp);

// create an empty nest data set
if (empty($temp)) {
    $temp[] = [
        'group' => 'posts',
        'time' => 0,
        'count' => 0
    ];
    $temp[] = [
        'group' => 'images',
        'time' => 0,
        'count' => 0
    ];
    $temp[] = [
        'group' => 'sage',
        'time' => 0,
        'count' => 0
    ];
}
?>

<div id="graphs"></div>

<script src="<?= $this->plugin->getAssetManager()->getAssetLink('d3/d3.v3.min.js') ?>" type="text/javascript"></script>
<script>
// d3.js
var m = [20, 30, 30, 60],
    w = 960 - m[3] - m[1],
    h = 500 - m[0] - m[2];
var x = d3.time.scale().range([0, w]);
var y = d3.scale.linear().range([h, 0]);

var color = d3.scale.ordinal()
    .range(["#008000", "#ff0000", "#0000ff"]);
var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom")
    .ticks(d3.time.months);
var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left");
var stack = d3.layout.stack()
    .offset("zero")
    .values(function(d) { return d.values; })
    .x(function(d) { return d.time; })
    .y(function(d) { return d.count; });
var nest = d3.nest()
    .key(function(d) { return d.group});
var line = d3.svg.line()
    .interpolate("basis")
    .x(function(d) { return x(d.time); })
    .y(function(d) { return y(d.y); });
var area = d3.svg.area()
    .interpolate("basis")
    .x(function(d) { return x(d.time); })
    .y0(h)
    .y1(function(d) { return y(d.y); });

// data
var data_board = <?= json_encode($temp) ?>;

data_board.forEach(function(d) {
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
x.domain(d3.extent(data_board, function(d) { return d.time; })).range([0, w]);
y.domain([0, d3.max(data_board, function(d) { return d.y + d.y0 + 2; })]).range([h, 0]);

    svg_board.selectAll(".layer")
        .data(layers)
        .enter().append("path")
        .attr("class", "layer")
        .attr("d", function(d) { return area(d.values); })
        .style("fill", function(d, i) { return color(i); })
        .attr("fill-opacity",".2");

    svg_board.selectAll(".line")
        .data(layers)
        .enter().append("path")
        .attr("class", "line")
        .attr("stroke", function(d, i) { return color(i); })
        .attr("d", function(d) { return line(d.values); })
        .style("fill", "none");

    svg_board.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + h + ")")
        .call(xAxis);

    svg_board.append("g")
        .attr("class", "y axis")
        .call(yAxis);

// graph legend
d3.selectAll("svg").each(function(d) {
    var e = d3.select(this);

    e.append("rect")
        .attr("x", 830)
        .attr("y", 15)
        .attr("width", 25).attr("height", 15)
        .style("stroke", color(0))
        .style("stroke-width","1px")
        .style("fill", color(0))
        .attr("fill-opacity",".2");

    e.append("text")
        .attr("x", 860)
        .attr("y", 27.5)
        .text("Anons");

    e.append("rect")
        .attr("x", 830)
        .attr("y", 40)
        .attr("width", 25).attr("height", 15)
        .style("stroke", color(1))
        .style("stroke-width","1px")
        .style("fill", color(1))
        .attr("fill-opacity",".2");

    e.append("text")
        .attr("x", 860)
        .attr("dy", 52.5)
        .text("Namefags");

    e.append("rect")
        .attr("x", 830)
        .attr("y", 65)
        .attr("width", 25).attr("height", 15)
        .style("stroke", color(2))
        .style("stroke-width","1px")
        .style("fill", color(2))
        .attr("fill-opacity",".2");

    e.append("text")
        .attr("x", 860)
        .attr("dy", 77.5)
        .text("Tripfriends");
});
</script>
