/**
 * csrf.js
 * --------------------------------------------------
 * • 페이지 로드 시 세션의 CSRF 토큰을 읽어
 *   모든 fetch / jQuery.ajax POST‧PUT‧DELETE 요청에
 *   "X-CSRF-TOKEN" 헤더를 자동으로 삽입한다.
 * • 별도 설정 없이 index.php · uzart.php 등에서
 *   <script src="js/csrf.js"></script> 로드하면 끝.
 * • 의존성: jQuery(선택)
 */

(function () {
  // 1) 메타 태그 또는 hidden input 에서 토큰 추출
  const token =
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="csrf_token"]')?.value ||
    "";

  if (!token) {
    console.warn("[csrf.js] CSRF token not found in DOM.");
    return;
  }

  /* ────────────────────────────────────────────────
   * 2) jQuery ajaxSetup  (존재할 때만 적용)
   * ─────────────────────────────────────────────── */
  if (window.jQuery) {
    $.ajaxSetup({
      beforeSend: function (xhr, settings) {
        const m = (settings.type || "GET").toUpperCase();
        if (["POST", "PUT", "DELETE", "PATCH"].includes(m)) {
          xhr.setRequestHeader("X-CSRF-TOKEN", token);
        }
      },
    });
  }

  /* ────────────────────────────────────────────────
   * 3) 원본 fetch() 래핑
   * ─────────────────────────────────────────────── */
  if (window.fetch) {
    const _fetch = window.fetch;
    window.fetch = function (input, init = {}) {
      const method = ((init.method || "GET") + "").toUpperCase();
      if (["POST", "PUT", "DELETE", "PATCH"].includes(method)) {
        init.headers = new Headers(init.headers || {});
        init.headers.set("X-CSRF-TOKEN", token);
      }
      return _fetch(input, init);
    };
  }

  console.info("[csrf.js] CSRF header auto-attach enabled.");
})();

