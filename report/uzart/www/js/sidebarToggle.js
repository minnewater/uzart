$(document).ready(function() {
    // 사이드바 토글
    $("#toggleSidebar").off('click').on('click', function() {
        $(".sidebar").toggleClass("collapsed");
    });

    // 메뉴 클릭 이벤트
    $(".menu-link").off('click').on('click', function(e) {
        e.preventDefault(); // 기본 이동 방지
        var href = $(this).attr('href');
        console.log("Menu clicked: " + href); // 디버깅용 로그
        window.location.href = href; // 단일 이동 보장
    });

    // 로그아웃 버튼 클릭
    $(".logout-btn").off('click').on('click', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        window.location.href = href;
    });
});
