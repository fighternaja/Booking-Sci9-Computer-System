# Recurring Booking Wizard Implementation Plan

## Goal
Implement a user-friendly "Wizard" UI for creating recurring bookings (e.g., "Every Monday for 3 months") with a conflict preview feature.

## 1. Backend Changes
- **New Endpoint**: `POST /api/recurring-bookings/check-conflicts`
- **Controller**: `RecurringBookingController.php`
    - Add `checkConflicts(Request $request)` method.
    - Logic: Simulate booking generation based on pattern, check availability for each date, and return a list of:
        - `valid_dates`: Dates that can be booked.
        - `conflicting_dates`: Dates that have conflicts (with reason/booking info).

## 2. Frontend Changes
- **New Component**: `components/RecurringBookingWizard.js`
    - **UI Structure**: Multi-step modal.
        - **Step 1: Pattern**: Select Weekly/Monthly/Daily.
        - **Step 2: Configuration**:
            - Week days selector (Mon-Sun).
            - Time range (Start/End).
            - Duration (End date or Number of occurrences).
        - **Step 3: Preview & Conflict Check**:
            - Call `check-conflicts` API.
            - Display list/calendar of dates.
            - Show Summary: "Total 12 bookings. 10 Available, 2 Conflict".
            - Option to "Book only available" or "Back to adjust".
        - **Step 4: Book**:
            - Enter Purpose/Notes.
            - Confirm booking.
- **Integration**:
    - Add "Book Multiple Days" (จองหลายวัน/แบบต่อเนื่อง) button in `BookRoomPage` (`app/rooms/[id]/book/page.js`) or `BookingCalendar` header.

## Verification Plan
### Automated Tests
- Test `checkConflicts` API with:
    - No conflict scenario.
    - Partial conflict scenario (some dates taken).
    - Full conflict scenario.

### Manual Verification
1. Open "Book Room" page.
2. Click "Recurring Booking".
3. Select "Weekly" -> "Mon, Wed" -> "Next 3 Months".
4. View Preview: Ensure dates are correct.
5. Create conflict manually (open another tab, book one of those Mondays).
6. Refresh Preview: Confirm that specific Monday shows as "Conflict".
7. Proceed to Book: Verify that multiple bookings are created in `My Bookings`.
