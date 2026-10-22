<script>
    var options =
    {
        chart: {
            type: '{!! $chart->type() !!}',
            height: {!! $chart->height() !!},
            width: '{!! $chart->width() !!}',
            toolbar: {!! $chart->toolbar() !!},
            zoom: {!! $chart->zoom() !!},
            fontFamily: '{!! $chart->fontFamily() !!}',
            foreColor: '{!! $chart->foreColor() !!}',
            sparkline: {!! $chart->sparkline() !!},
            @if($chart->stacked())
            stacked: {!! $chart->stacked() !!},
            @endif
        },
        plotOptions: {
            bar: {!! $chart->horizontal() !!}
        },
        colors: {!! $chart->colors() !!},
        series: {!! $chart->dataset() !!},
        dataLabels: {!! $chart->dataLabels() !!},
        @if($chart->labels())
            labels: {!! json_encode($chart->labels(), true) !!},
        @endif
        title: {
            text: "{!! $chart->title() !!}"
        },
        subtitle: {
            text: '{!! $chart->subtitle() !!}',
            align: '{!! $chart->subtitlePosition() !!}'
        },
        xaxis: {!! $chart->xAxis() !!},
        grid: {!! $chart->grid() !!},
        markers: {!! $chart->markers() !!},
        @if($chart->stroke())
            stroke: {!! $chart->stroke() !!},
        @endif
    }

    var chart = new ApexCharts(document.querySelector("#{!! $chart->id() !!}"), options);
    chart.render();

    @if(in_array($chart->type(), ['pie', 'donut']))
    // Pie tooltips only follow a held finger, replay a tap as a hover so it shows the same tooltip
    (function (el) {
        el.addEventListener('touchend', function (e) {
            var slice = e.target.closest('.apexcharts-series');
            if (!slice) return;
            var touch = e.changedTouches[0];
            // Wait for the tap's emulated mouse events, the tooltip throttle keeps only the last event
            setTimeout(function () {
                var move = { clientX: touch.clientX, clientY: touch.clientY, bubbles: true };
                // The chart reads the pointer position from its root element, so set that first
                el.dispatchEvent(new MouseEvent('mousemove', move));
                slice.dispatchEvent(new MouseEvent('mousemove', move));
            }, 50);
        }, { passive: true });
    })(document.querySelector("#{!! $chart->id() !!}"));
    @endif

</script>
