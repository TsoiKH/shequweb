@extends('layouts.admin')

@section('title', '控制台')

@section('content')
<style>
    .stat-card {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        text-align: center;
    }
    .stat-card h3 {
        font-size: 16px;
        color: #6c757d;
        margin: 0 0 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-card .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #2c3e50;
    }
    .stat-card .stat-today {
        font-size: 14px;
        color: #28a745;
    }
    .chart-container {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        margin-top: 25px;
    }
</style>

<div class="container-fluid">
    <h2 style="margin-bottom: 25px; font-weight: 300;">数据概览</h2>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <h3>总用户数</h3>
                <div class="stat-number">{{ $stats['total_users'] }}</div>
                <div class="stat-today">今日新增: +{{ $stats['today_users'] }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <h3>总帖子数</h3>
                <div class="stat-number">{{ $stats['total_posts'] }}</div>
                <div class="stat-today">今日新增: +{{ $stats['today_posts'] }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <h3>总评论数</h3>
                <div class="stat-number">{{ $stats['total_comments'] }}</div>
            </div>
        </div>
    </div>

    {{-- 趋势图表 --}}
    <div class="row">
        <div class="col-12">
            <div class="chart-container">
                <h3 style="font-size: 16px; color: #6c757d; margin-bottom: 20px;">过去 7 天增长趋势</h3>
                <canvas id="growthChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['labels']) !!},
            datasets: [{
                label: '新增用户',
                data: {!! json_encode($chartData['users']) !!},
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: '新增帖子',
                data: {!! json_encode($chartData['posts']) !!},
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
</script>
@endsection
