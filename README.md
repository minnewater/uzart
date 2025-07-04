# Uzart

## 프로젝트 소개
**Uzart**는 서버 점검 및 보고서 생성을 위한 도구입니다.  
점검 기록 관리와 보고서 작성 기능을 제공합니다.

---

## 주요 기능
- 서버 점검 이력 기록
- 점검 결과 보고서 자동 생성
- 점검 내역 조회 및 관리

---

## 기술 스택
- **AS-IS:** PHP
- **TO-BE:** Java

---

## 설치 방법
1. 저장소 클론
   ```sh
   git clone https://github.com/minnewater/uzart.git
   ```
2. (추가 설치 방법 및 환경 설정은 추후 업데이트 예정)
3. Java 서버 빌드 및 실행
   ```sh
   cd java
   mvn spring-boot:run
   # 또는
   mvn package
   java -jar target/uzart-0.0.1-SNAPSHOT.jar
   ```

---

## 사용법
- 서버 점검 기능 실행
- 점검 완료 후 자동으로 보고서 생성
- 생성된 보고서 확인 및 다운로드

(자세한 사용법은 추후 문서화 예정)

---

## 라이선스
이 프로젝트의 라이선스는 별도로 명시되지 않았습니다.

---

## 문의 및 기여
프로젝트에 기여를 원하거나 문의 사항이 있으시면  
[GitHub Issues](https://github.com/minnewater/uzart/issues)를 이용해주세요.
