<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>２都市間の時差・所要時間 計算 | flight-time-calculator</title>
    <meta name="description" content="2地点の現地日時から時差と所要時間を算出するWebサービス">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
</head>

<body class="app-body">
    <main class="container py-4 py-lg-5">
        <section class="page-hero mb-4">
            <div class="d-flex flex-column gap-2">
                <span class="eyebrow">flight-time-calculator</span>
                <h1 class="display-6 fw-semibold mb-0">２都市間の時差・所要時間 計算</h1>
                <p class="lead text-secondary mb-0">
                    世界中の主要な飛行移動地点を対象に、２都市間の時差と所要時間を算出します。
                </p>
            </div>
        </section>

        <section class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-lg-5">
                <form id="flight-form" novalidate>
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-5">
                            <section class="location-panel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <p class="section-kicker mb-1">Departure</p>
                                        <h2 class="h4 mb-0">出発</h2>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary favorite-toggle" data-target="from">
                                        お気に入り追加
                                    </button>
                                </div>

                                <div class="mb-3">
                                    <label for="from_id" class="form-label">出発地点</label>
                                    <select name="from_id" id="from_id" class="form-select">
                                        <option value="">都市名・国名・空港コードで検索</option>
                                    </select>
                                    <div class="form-text">文字を入力すると候補が絞り込まれます。</div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="departure_date" class="form-label">出発日</label>
                                        <input type="date" class="form-control" name="departure_date" id="departure_date" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="departure_time" class="form-label">出発時刻</label>
                                        <input type="time" class="form-control" name="departure_time" id="departure_time" required>
                                    </div>
                                </div>

                                <div class="meta-card mt-3" id="from_meta">
                                    <div class="meta-card__empty">地点を選択すると、国名・timezone_id・UTCオフセット・サマータイム状態を表示します。</div>
                                </div>
                            </section>
                        </div>

                        <div class="col-lg-2 d-flex justify-content-center align-items-center">
                            <div class="swap-wrap">
                                <button type="button" class="btn btn-outline-primary swap-button rounded-circle" id="swap_button" aria-label="出発と到着を入れ替える">⇄</button>
                                <p class="swap-help text-secondary mb-0">入れ替え</p>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <section class="location-panel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <p class="section-kicker mb-1">Arrival</p>
                                        <h2 class="h4 mb-0">到着</h2>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary favorite-toggle" data-target="to">
                                        お気に入り追加
                                    </button>
                                </div>

                                <div class="mb-3">
                                    <label for="to_id" class="form-label">到着地点</label>
                                    <select name="to_id" id="to_id" class="form-select">
                                        <option value="">都市名・国名・空港コードで検索</option>
                                    </select>
                                    <div class="form-text">同名の候補は国名付きで表示します。</div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="arrival_date" class="form-label">到着日</label>
                                        <input type="date" class="form-control" name="arrival_date" id="arrival_date" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="arrival_time" class="form-label">到着時刻</label>
                                        <input type="time" class="form-control" name="arrival_time" id="arrival_time" required>
                                    </div>
                                </div>

                                <div class="meta-card mt-3" id="to_meta">
                                    <div class="meta-card__empty">地点を選択すると、国名・timezone_id・UTCオフセット・サマータイム状態を表示します。</div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">計算する</button>
                        <button type="button" class="btn btn-outline-secondary" id="reset_button">入力をリセット</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card border-0 shadow-sm rounded-4 mb-4 result-card" id="result_card">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <p class="section-kicker mb-1">Result</p>
                        <h2 class="h4 mb-0">計算結果</h2>
                    </div>
                    <div id="result_status" class="badge text-bg-light">未計算</div>
                </div>

                <div id="result_error" class="alert alert-danger d-none mb-3" role="alert"></div>

                <div id="result_empty" class="result-empty">
                    出発地点・到着地点・各日時を入力して「計算する」を押してください。
                </div>

                <div id="result_content" class="d-none">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="summary-tile">
                                <div class="summary-tile__label">所要時間</div>
                                <div class="summary-tile__value" id="duration_text">--</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="summary-tile">
                                <div class="summary-tile__label">時差</div>
                                <div class="summary-tile__value" id="timezone_diff_text">--</div>
                            </div>
                        </div>
                    </div>
                    <div class="result-foot mt-4">
                        <strong id="arrival_day_offset_text">--</strong>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="detail-block">
                                <h3 class="h6 mb-3">出発</h3>
                                <dl class="detail-list mb-0">
                                    <div>
                                        <dt>地点</dt>
                                        <dd id="from_city_label">--</dd>
                                    </div>
                                    <div>
                                        <dt>現地日時</dt>
                                        <dd id="departure_local_text">--</dd>
                                    </div>
                                    <div>
                                        <dt>timezone_id</dt>
                                        <dd id="from_timezone_id">--</dd>
                                    </div>
                                    <div>
                                        <dt>UTC</dt>
                                        <dd id="departure_utc_text">--</dd>
                                    </div>
                                    <div>
                                        <dt>UTCオフセット</dt>
                                        <dd id="from_offset_text">--</dd>
                                    </div>
                                    <div>
                                        <dt>サマータイム</dt>
                                        <dd id="from_dst_text">--</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="detail-block">
                                <h3 class="h6 mb-3">到着</h3>
                                <dl class="detail-list mb-0">
                                    <div>
                                        <dt>地点</dt>
                                        <dd id="to_city_label">--</dd>
                                    </div>
                                    <div>
                                        <dt>現地日時</dt>
                                        <dd id="arrival_local_text">--</dd>
                                    </div>
                                    <div>
                                        <dt>timezone_id</dt>
                                        <dd id="to_timezone_id">--</dd>
                                    </div>
                                    <div>
                                        <dt>UTC</dt>
                                        <dd id="arrival_utc_text">--</dd>
                                    </div>
                                    <div>
                                        <dt>UTCオフセット</dt>
                                        <dd id="to_offset_text">--</dd>
                                    </div>
                                    <div>
                                        <dt>サマータイム</dt>
                                        <dd id="to_dst_text">--</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="section-kicker mb-1">Favorites</p>
                                <h2 class="h5 mb-0">お気に入り地点</h2>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear_favorites">全削除</button>
                        </div>
                        <div id="favorites_empty" class="text-secondary small">よく使う地点を保存すると、ここに表示されます。</div>
                        <div id="favorites_list" class="stack-list"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="section-kicker mb-1">Recent</p>
                                <h2 class="h5 mb-0">最近使った地点</h2>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear_recent">全削除</button>
                        </div>
                        <div id="recent_empty" class="text-secondary small">計算に使った地点を自動で保存します。</div>
                        <div id="recent_list" class="stack-list"></div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script src="./assets/js/storage.js"></script>
    <script src="./assets/js/city-select.js"></script>
    <script src="./assets/js/calculator.js"></script>
    <script src="./assets/js/ui.js"></script>
    <script src="./assets/js/app.js"></script>
</body>

</html>