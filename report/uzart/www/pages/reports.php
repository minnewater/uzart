<?php
include_once __DIR__ . "/../../include/_common.php";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>📊 보고서</title>
    <link rel="stylesheet" href="/uzart/www/css/style.css">
    <script src="/uzart/www/js/pdf.min.js"></script>
    <script src="/uzart/www/js/pdf.worker.min.js"></script>
</head>
<body>
    <div class="content">
        <h1>📊 보고서</h1>
        <div class="filter-container">
            <form id="reportForm" class="filter-form">
                <div class="form-left">
                    <label for="start_date">시작일자:</label>
                    <input type="date" id="start_date" name="start_date">
                    <label for="end_date">마지막일자:</label>
                    <input type="date" id="end_date" name="end_date">
                    <label for="client">Client:</label>
                    <select id="client" name="client">
                        <option value="">-- 선택 --</option>
                    </select>
                </div>
                <div class="form-right">
                    <button type="button" id="searchReport" class="search-button">조회</button>
                    <button type="button" id="checkReport" class="action-button" disabled>점검</button>
                    <button type="button" id="viewReport" class="action-button" disabled>보고서</button>
                    <button type="button" id="downloadReport" class="action-button" disabled>다운로드</button>
                </div>
            </form>
        </div>

        <div id="reportTable">
            <h3>📑 보고서 데이터</h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>
                            선택
                            <button type="button" id="selectAll" style="font-size:12px; margin-left:5px;">(전체선택)</button>
                        </th>
                        <th>Server Name</th>
                        <th>Client</th>
                        <th>생성 시간</th>
                        <th>점검 여부</th>
                    </tr>
                </thead>
                <tbody id="reportResults">
                    <tr><td colspan="5">조회된 데이터가 없습니다.</td></tr>
                </tbody>
            </table>
            <div id="pagination"></div>
        </div>

        <div id="pdfModal" class="modal">
            <div class="modal-content-view">
                <span class="close-btn">&times;</span>
                <h2>📄 보고서</h2>
                <div id="pdfViewer"></div>
                <div class="modal-controls">
                    <button id="prevPage" disabled>⬅ 이전 페이지</button>
                    <span id="pageInfo">1 / 1</span>
                    <button id="nextPage" disabled>다음 페이지 ➡</button>
                </div>
                <div class="modal-controls">
                    <a id="modalDownloadReport" class="button" href="#" download>보고서 다운로드</a>
                </div>
            </div>
        </div>

        <div id="checkReportModal" class="modal">
            <div class="modal-content-view">
                <span class="cancel-btn">&times;</span>
                <div id="chkViewer"></div>
            </div>
        </div>

        <div id="alertMessage" class="alert-message"></div>
    </div>
</body>
</html>
