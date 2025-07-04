# Uzart

**Uzart**는 서버 상태 정보를 수집하고 리포트를 생성하기 위한 하이브리드 프로젝트입니다.  
이 저장소는 Java 기반의 Spring Boot 백엔드와 레거시 PHP 인터페이스로 구성되어 있습니다.

## 📁 프로젝트 구조
```
uzart/
├── java/ # Spring Boot 서비스
│ ├── src/main/java/com/example/uzart
│ │ ├── UzartApplication.java # 애플리케이션 엔트리포인트
│ │ ├── controller/DataController.java
│ │ ├── model/RawPayload.java
│ │ └── repository/RawPayloadRepository.java
│ ├── src/main/resources/application.properties
│ └── pom.xml
├── report/ # 레거시 PHP 인터페이스
│ ├── _common.php
│ ├── api.php
│ └── uzart/
│ ├── include/
│ ├── config/
│ ├── www/ # 로그인 폼, 대시보드
│ └── scripts/ # create_admin.php 등 CLI 유틸
└── .env.example # 데이터베이스 설정 템플릿
```

---

## 🚀 설치 및 실행 방법

### 1. 저장소 클론

```bash
git clone https://github.com/minnewater/uzart.git
cd uzart
```

2. 환경 변수 설정
.env.example 파일을 복사하여 .env로 생성한 후, PostgreSQL 접속 정보를 설정하세요.
```bash
cp .env.example .env
# .env 파일을 열어 DB 호스트, 이름, 사용자, 비밀번호 수정
```

3. JAVA 서버 실행
```bash
cd java
mvn spring-boot:run
```
기본적으로 포트 8080에서 서버가 실행되며, /api POST 엔드포인트가 제공됩니다.

4. PHP Report Interface
report/ 디렉터리에는 기존 PHP 애플리케이션이 있습니다. PHP 8을 지원하는 웹 서버를 통해 report/uzart를 문서 루트로 설정하면 사용할 수 있습니다.

5. Admin 계정 생성
```bash
cd report/uzart/scripts
php create_admin.php <username> <password>
스크립트 실행 후 출력된 ID 값을 확인합니다.
```

Building a JAR
배포용 JAR 파일 생성:
```bash
cd java
mvn package
java -jar target/uzart-0.0.1.SNAPSHOT.jar
```

Technology Stack
Java 17, Spring Boot 3

PHP 8 (레거시 대시보드)

PostgreSQL 사용

기존 PHP 기능을 점차 Java 서비스로 이전하는 과정 중이며, 현재 Java 서비스는 원본 페이로드 저장 기능을 제공합니다.

License
저장소에서 명시한 라이선스가 없습니다. 배포나 사용 전 유지관리자와 상의하세요.

Contributing
이슈나 PR은 GitHub에서 자유롭게 제안해 주세요.
