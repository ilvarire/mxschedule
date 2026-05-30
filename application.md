# Architectural and Functional Analysis of the Smart Exam Scheduling & Allocation Platform

## 1. Overall Purpose and Problem Statement

The **Smart Exam Scheduling & Allocation Platform** is designed to solve the logistical and administrative bottlenecks associated with mass e-examinations in university environments. Traditionally, computer-based testing (CBT) suffers from physical congestion, long wait times, unfair system allocation, and susceptibility to cheating (e.g., impersonation or targeting specific machines).

This application resolves these issues by automating the scheduling process, implementing a Software-Defined Networking (SDN) approach to physical computer allocation, and enforcing strict entry validation through cryptographically secure QR-coded passes. The platform ensures fair randomization of seating, offline resilience for invigilators, and real-time resource management for ICT administrators.

## 2. System Architecture and Design Approach

The system follows a **Monolithic Layered Architecture** using the MVC (Model-View-Controller) design pattern, augmented by service classes and background workers to handle heavy processing tasks asynchronously.

### 2.1 SDN-Inspired Resource Management
The core design philosophy treats physical computer systems as logical nodes that can be dynamically routed.
- **Halls** act as subnets or clusters.
- **Systems** act as individual nodes that possess state (`Active`, `Inactive`, `Faulty`).
When a node becomes faulty, the system dynamically reroutes the "packet" (the student) to an available node within the same cluster without disrupting the overall exam flow.

### 2.2 Offline-First Edge Validation
A major architectural decision is the hybrid validation approach. While the central server manages state, edge clients (Invigilator mobile apps) can operate in fully offline environments. This is achieved via **Asymmetric Cryptography** (RSA). The server signs exam passes with a private key, and edge devices verify authenticity using a pre-fetched public key, ensuring zero reliance on continuous network connectivity during exam check-ins.

## 3. Methodology Used

- **Design**: The system employs a Domain-Driven Design (DDD) approach, separating logic into bounded contexts such as Scheduling, Validation, and Reporting. 
- **Implementation**: Developed using Laravel, leveraging its robust ORM (Eloquent), Job Queues, and Event Broadcasting. The system implements pessimistic database locking (`lockForUpdate`) to guarantee ACID compliance during concurrent scheduling requests.
- **Evaluation**: The platform's efficacy is evaluated via built-in analytical reporting tools that track attendance rates, system failure frequencies, and load distribution across exam halls.

## 4. Key Components, Modules, and Interactions

### 4.1 Scheduling Engine (`SchedulingEngine.php`)
The brain of the platform. It takes inputs (total students, active systems, duration) and outputs discrete `ExamSession` and `ExamAllocation` records.
- **Interaction**: Uses Fisher-Yates cryptographic shuffling to randomly assign students to systems, effectively mitigating premeditated cheating. Enforces daily operating constraints (e.g., 8 AM - 6 PM).

### 4.2 QR Validation Module (`QrValidationService.php`)
Handles the ingress of students into the exam hall.
- **Interaction**: Decodes JSON payloads embedded in QR codes, verifies the RSA signature using `openssl_verify`, and validates temporal constraints (e.g., entry window limits). Upon success, it updates the `ExamAllocation` state to `CheckedIn`.

### 4.3 Offline Synchronization Module (`OfflineSyncController.php`)
Manages the reconciliation of offline scan logs with the central database.
- **Interaction**: Implements conflict resolution. If an offline check-in conflicts with an existing online check-in, the system flags the log as a `CONFLICT` rather than overwriting data, maintaining an immutable audit trail in `AttendanceLog`.

### 4.4 Reallocation Service (`ReallocationService.php`)
Handles fault tolerance mid-exam.
- **Interaction**: When an ICT Admin marks a system as `Faulty` via the `SystemController`, this service intercepts active sessions (`end_time > now()`), searches for available fallback systems, dynamically reassigns the student, and regenerates their pass.

## 5. Technologies, Frameworks, and Tools

- **Backend Framework**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL/PostgreSQL (leveraging Eloquent ORM and pessimistic locking).
- **Authentication & Authorization**: Laravel Breeze with Spatie Laravel-Permission for strict Role-Based Access Control (RBAC).
- **Frontend Stack**: Laravel Blade templating combined with Livewire/Vue.js for reactive, dynamic administrative dashboards. Tailwind CSS for UI styling.
- **Cryptography**: OpenSSL for generating and verifying 2048-bit RSA signatures.
- **Document Generation**: `barryvdh/laravel-dompdf` for generating printable A4 exam passes.
- **Background Processing**: Redis/Database Queues for offloading PDF generation and Email/SMS notifications.

## 6. Data Flow and Processing Logic

1. **Ingestion**: Students and courses are imported via CSV (`CsvImportService`). Accounts are provisioned automatically.
2. **Configuration**: Exam Officers create an `Exam` instance. ICT Admins ensure `Halls` and `Systems` are marked `Active`.
3. **Execution (Scheduling)**: 
   - A background job (`GenerateExamScheduleJob`) is dispatched.
   - The engine locks the systems table, calculates required sessions, shuffles students, and writes `ExamAllocation` records.
4. **Distribution**: `ExamPassService` generates a unique hash, signs the payload, stores it in `ExamPass`, and dispatches a job to generate a PDF.
5. **Validation**: The Invigilator scans the pass. The payload is decoded, signature verified, and the `seat_status` transitions from `Allocated` to `CheckedIn`.
6. **Reporting**: Post-exam, `ReportService` aggregates data to display attendance rates and system usage statistics.

## 7. Real-World Application and Use Cases

- **University Final Examinations**: Scaling to thousands of students across multiple faculties over a one-week period.
- **Professional Certification Testing**: Ensuring strict compliance, randomized seating, and indisputable entry logs.
- **Resource Management**: Providing ICT departments with real-time heatmaps of hardware degradation (e.g., tracking which halls have the highest rate of faulty systems).

## 8. Assumptions, Limitations, and Potential Improvements

### Assumptions
- The server environment has access to OpenSSL binaries for key generation.
- Invigilator devices have sufficiently accurate internal clocks, as temporal validation in offline mode relies on the device's local time.

### Limitations
- **Offline Clock Drift**: If an invigilator's offline device has an incorrect time, it could erroneously reject valid passes or accept expired ones.
- **Static Batching**: The current scheduling engine assumes fixed batch sizes based on total active systems at the exact moment of scheduling. 

### Potential Improvements
1. **Dynamic Real-Time Rebalancing**: Instead of static time slots, the system could use a continuous queue model where students enter the hall as soon as any system frees up, increasing throughput.
2. **NTP Sync Enforcement**: The offline client application should enforce a Network Time Protocol (NTP) sync check before allowing the invigilator to go into "Offline Mode" to prevent clock drift vulnerabilities.
3. **WebSockets for Dashboards**: Implementing Laravel Reverb to push system status changes (e.g., a system breaking down) to the Exam Officer's dashboard in real-time without requiring page refreshes.
