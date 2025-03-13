<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>📜 로그 관리</title>
    <link rel="stylesheet" href="/uzart/www/css/style.css">
</head>
<body>
    <div class="content">
        <h1>📜 로그 관리</h1>
        <div class="filter-container">
            <form id="logFilterForm" class="filter-form">
                <div class="form-left">
                    <label>시작일자:
                        <input type="date" name="start_date" id="start_date">
                    </label>
                    <label>마지막일자:
                        <input type="date" name="end_date" id="end_date">
                    </label>
                    <label>Client:
                        <input type="text" name="client" id="client" placeholder="클라이언트 이름">
                    </label>
                    <label>Server Name:
                        <input type="text" name="server_name" id="server_name" placeholder="서버 이름">
                    </label>
                    <label>Client IP:
                        <input type="text" name="client_ip" id="client_ip" placeholder="클라이언트 IP">
                    </label>
                    <label>표시 개수:
                        <select name="limit" id="limit">
                            <option value="10">10개</option>
                            <option value="20" selected>20개</option>
                            <option value="30">30개</option>
                            <option value="40">40개</option>
                            <option value="50">50개</option>
                        </select>
                    </label>
                </div>
                <div class="form-right">
                    <button type="submit" class="search-button">조회</button>
                </div>
            </form>
        </div>

        <table id="logTable">
            <thead>
                <tr>
                    <th>시간</th>
                    <th>레벨</th>
                    <th>메시지</th>
                    <th>클라이언트 이름</th>
                    <th>서버 이름</th>
                    <th>클라이언트 IP</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" style="text-align: center;">조회된 로그가 없습니다.</td>
                </tr>
            </tbody>
        </table>

        <div id="pagination"></div>
    </div>
</body>
</html>
