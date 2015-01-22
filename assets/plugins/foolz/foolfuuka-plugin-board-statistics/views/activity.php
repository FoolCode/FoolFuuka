<?php
$data_array = json_decode($data);

$temp = [];

foreach ($data_array as $k => $t) {
    $temp[$k] = [];

    foreach ($t as $kS => $tS) {
        $timestamp = $tS->time;

        if ($this->radix->archive) {
            $datetime = new \DateTime(date('Y-m-d H:i:s', $timestamp), new \DateTimeZone('America/New_York'));
            $datetime->setTimezone(new \DateTimeZone('UTC'));
            $timestamp = strtotime($datetime->format('Y-m-d H:i:s'));
        }

        $temp[$k][] = [
            'group' => 'posts',
            'time' => $timestamp,
            'count' => $tS->posts
        ];
        $temp[$k][] = [
            'group' => 'images',
            'time' => $timestamp,
            'count' => $tS->images
        ];
        $temp[$k][] = [
            'group' => 'sage',
            'time' => $timestamp,
            'count' => $tS->sage
        ];
    }

    // manually truncate results
    array_pop($temp[$k]);
    array_pop($temp[$k]);
    array_pop($temp[$k]);

    // create an empty nest data set
    if (empty($temp[$k])) {
        $temp[$k][] = [
            'group' => 'posts',
            'time' => 0,
            'count' => 0
        ];
        $temp[$k][] = [
            'group' => 'images',
            'time' => 0,
            'count' => 0
        ];
        $temp[$k][] = [
            'group' => 'sage',
            'time' => 0,
            'count' => 0
        ];
    }
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
var z = d3.scale.category20c();

var color = d3.scale.ordinal()
    .range(["#008000", "#0000ff", "#ff0000"]);
var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom")
    .ticks(d3.time.hours, 2);
var xAxisScaler = d3.svg.axis()
    .scale(x)
    .orient("bottom");
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
var line = d3.svg.line()
    .interpolate("basis")
    .x(function(d) { return x(d.time); })
    .y(function(d) { return y(d.y); });
var lineAvg = d3.svg.line()
    .interpolate("basis")
    .x(function(d,i) { return x(d.time); })
    .y(function(d,i) { return y(d.y); });
var area = d3.svg.area()
    .interpolate("basis")
    .x(function(d) { return x(d.time); })
    .y0(h)
    .y1(function(d) { return y(d.y); });

// data
var data_board = <?= json_encode($temp['board']) ?>;
var data_ghost = <?= json_encode($temp['ghost']) ?>;
var data_karma = <?= json_encode($temp['karma']) ?>;
var data_total = <?= json_encode($temp['total']) ?>;

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

data_total.forEach(function(d) {
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

    svg_board.append("text")
        .attr("x", 10)
        .attr("dy", 20)
        .text("Activity (Past 24 Hours)")
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
        .style("fill", function(d, i) { return color(i); })
        .attr("fill-opacity",".2");

    svg_ghost.selectAll(".line")
        .data(layers)
        .enter().append("path")
        .attr("class", "line")
        .attr("stroke", function(d, i) { return color(i); })
        .attr("d", function(d) { return line(d.values); })
        .style("fill", "none");

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
        .text("Ghost Activity (Past 24 Hours)")
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
        .style("fill", function(d, i) { return color(i); })
        .attr("fill-opacity",".2");

    svg_karma.selectAll(".line")
        .data(layers)
        .enter().append("path")
        .attr("class", "line")
        .attr("stroke", function(d, i) { return color(i); })
        .attr("d", function(d) { return line(d.values); })
        .style("fill", "none");

    svg_karma.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + h + ")")
        .call(d3.svg.axis().scale(x).orient("bottom").ticks(d3.time.months));

    svg_karma.append("g")
        .attr("class", "y axis")
        .call(yAxis);

    svg_karma.append("text")
        .attr("x", 10)
        .attr("dy", 20)
        .text("Activity (Past Year)")
        .style("font-weight", "bold");

var svg_total = d3.select("#graphs").append("svg")
    .attr("width", w + m[3] + m[1])
    .attr("height", h + m[0] + m[2])
    .append("g")
    .attr("transform", "translate(" + m[3] + "," + m[0] + ")");

var layers = stack(nest.entries(data_total));
x.domain(d3.extent(data_total, function(d) { return d.time; }));
y.domain([0, d3.max(data_total, function(d) { return d.y + d.y0 + 2; })]).range([h, 0]);

    svg_total.selectAll(".layer")
        .data(layers)
        .enter().append("path")
        .attr("class", "layer")
        .attr("d", function(d) { return area(d.values); })
        .style("fill", function(d, i) { return color(i); })
        .attr("fill-opacity",".2");

    svg_total.selectAll(".line")
        .data(layers)
        .enter().append("path")
        .attr("class", "line")
        .attr("stroke", function(d, i) { return color(i); })
        .attr("d", function(d) { return lineAvg(d.values); })
        .style("fill", "none");

    svg_total.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + h + ")")
        .call(xAxisScaler);

    svg_total.append("g")
        .attr("class", "y axis")
        .call(yAxis);

    svg_total.append("text")
        .attr("x", 10)
        .attr("dy", 20)
        .text("Activity (Total)")
        .style("font-weight", "bold");

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
        .text("Posts");

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
        .text("Images");

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
        .text("Sages");
});
</script>
