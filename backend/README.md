# 🌐 Healing Journey — API Documentation

Technical reference for the Healing Journey API endpoints, describing the communication contract between the **Backend** and **Frontend**.

---

## 📑 Table of Contents

1. [Backend Installation](#-backend-installation)
2. [General Information](#-general-information)
3. [Public Routes](#-public-routes)
4. [Authentication](#-authentication)
5. [Account Status Handling (Frontend Logic)](#-account-status-handling-frontend-logic)
6. [Admin — Account Verification](#-admin--account-verification)
7. [Profile API](#-profile-api)
8. [Settings API](#-settings-api)
9. [Parent & Children Management](#-parent--children-management)
10. [Child Content Management](#-child-content-management)
11. [Recovered Child Profile API](#-recovered-child-profile-api)
12. [Specialist — Sessions & Activities](#-specialist--sessions--activities)
13. [Awareness & Motivational Content](#-awareness--motivational-content)
14. [Donor Campaigns](#-donor-campaigns)
15. [Central Content Hub Routes](#-central-content-hub-routes)
16. [Cover Image Logic](#️-cover-image-logic)
17. [Auto Re-Review (Update) Logic](#-auto-re-review-update-logic)
18. [Operation Restrictions](#-operation-restrictions)
19. [Interaction System (Likes & Comments)](#-interaction-system-likes--comments)
20. [Admin Dashboard API](#-admin-dashboard-api)
21. [Admin Content Moderation (Actions)](#-admin-content-moderation-actions)
22. [Admin — User Management](#-admin--user-management)
23. [System Settings (Public & Admin)](#-system-settings-public--admin)
24. [Categories API](#-categories-api)
25. [Contact Messages API](#-contact-messages-api)
26. [Developer Notes](#-developer-notes)

---

# ⚙️ Backend Installation

Follow the steps below to set up the Laravel backend locally.

```bash
# Install PHP dependencies
composer install

# Create environment configuration
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database credentials inside the .env file

# Run database migrations and seed the database
php artisan migrate --seed

# Create the storage symbolic link
php artisan storage:link

# Start the development server
php artisan serve
```

The API will be available at:

```
http://127.0.0.1:8000/api
```

> **Note:** Make sure to configure the database connection and settings inside the `.env` file before running the application.

---

## 🛠️ General Information

**Base URL:**

```
http://127.0.0.1:8000/api
```

**Authentication:**
All routes require the header below unless stated otherwise:

```
Authorization: Bearer {token}
```

**Public (no token required):**

- `POST /register`
- `POST /login`
- `GET /home`
- `POST /contact`
- `GET /system/settings`

**File Uploads:**
Use `multipart/form-data` for every request that includes a file.

**Storage Setup:**

```bash
php artisan storage:link
```

---

## 🏠 Public Routes

### Home Endpoint (Public Dashboard)

**GET** `/home`

Returns platform-wide overview data used for the main public dashboard. No authentication required.

**Stats explanation:**

| Field                    | Description                                 |
| ------------------------ | ------------------------------------------- |
| `beneficiaries`          | Number of child recovery profiles           |
| `awareness_and_sessions` | Total awareness content + activity sessions |
| `campaigns`              | Total donation campaigns                    |
| `children_content`       | Total child-generated content               |

**Latest Recovered Children:**
Returns the latest 3 **approved** recovered children only. Each child includes:

- `id`
- `full_name`
- `recovery_story`
- `nickname`

---

## 🔐 Authentication

| Method | Endpoint    | Description                              |
| ------ | ----------- | ---------------------------------------- |
| POST   | `/register` | Register a new user                      |
| POST   | `/login`    | Login and check account status           |
| POST   | `/logout`   | Logout the user and invalidate the token |

🔑 Default Admin Account: Seeder
Email: admin@system.com
Password: password123

### Logout Behavior

- The current access token is immediately revoked.
- The token cannot be reused after logout.
- Any request using the same token after logout returns `401 Unauthorized`.
- The user remains in the system but must log in again to receive a new token.

### Authentication Notes

- Token-based authentication (Sanctum or similar).
- The token must be sent in the `Authorization` header.
- Each login generates a new valid token.
- Old tokens remain valid until logout or manual revocation.

---

## 🛠 Account Status Handling (Frontend Logic)

The table below describes how the frontend should handle the different account statuses returned on login.

| Account Status | Server Response | Required Action                                                       |
| -------------- | --------------- | --------------------------------------------------------------------- |
| Approved       | `200 OK`        | Redirect to the main dashboard                                        |
| Pending        | `403 Forbidden` | Show message: _"تم تسجيل حسابك بنجاح، بانتظار مراجعة الأدمن."_        |
| Rejected       | `403 Forbidden` | Show the rejection message along with the reason (`rejection_reason`) |

**Rejected account response example:**

```json
{
    "message": "تم رفض الطلب.",
    "rejection_reason": "يرجى مراجعة البيانات وإعادة المحاولة."
}
```

---

## 🛡 Admin — Account Verification

| Method | Endpoint                                    | Description                    |
| ------ | ------------------------------------------- | ------------------------------ |
| GET    | `/admin/verifications`                      | List all pending verifications |
| GET    | `/admin/verifications/{userId}`             | Get user details               |
| POST   | `/admin/verifications/{userId}/approve`     | Approve account                |
| POST   | `/admin/verifications/{userId}/reject`      | Reject account                 |
| GET    | `/admin/documents/{userId}/view/{type}`     | View a document                |
| GET    | `/admin/documents/{userId}/download/{type}` | Download a document            |

**Allowed `{type}` values:**

- `identity_card`
- `certificate`

---

## 👤 Profile API

| Method | Endpoint          | Description                       |
| ------ | ----------------- | --------------------------------- |
| GET    | `/profile`        | View the current user's profile   |
| POST   | `/profile/update` | Update the current user's profile |

**Notes:**

- Viewing (`GET /profile`) is available to all registered users.
- Updating (`POST /profile/update`) is available to **Specialist** and **Recovered Child** roles.

---

## ⚙️ Settings API

### Get Settings

```
GET /api/settings
```

Displays the current user's data.

The profile picture is returned **only** for the following roles:

- Admin
- Donor
- Parent

### Update Settings

```
POST /api/settings/update
```

Supported fields:

- `full_name`
- `email`
- `phone`
- `job_title`
- `current_password`
- `new_password`
- `profile_picture`

Uploading a profile picture is allowed **only** for:

- Admin
- Donor
- Parent

---

## 👨‍👧 Parent & Children Management

This group of routes allows a parent to manage their children's profiles, as well as create and manage content on behalf of each child.

### 🔄 Recommended Workflow

It is recommended to test and use the routes in the following order:

1. Add a new child.
2. Edit the child's data (as needed).
3. Create new content for the child.
4. Edit or delete the content.
5. View the child's full profile to confirm all related content appears correctly.
6. Delete the child (as needed).

### 👶 Child Profile Routes

| Method | Endpoint                     | Description                                    |
| ------ | ---------------------------- | ---------------------------------------------- |
| GET    | `/parent/children`           | List all children linked to the current parent |
| GET    | `/parent/children/{childId}` | View the full profile of a child               |
| POST   | `/parent/children`           | Add a new child                                |
| POST   | `/parent/children/{childId}` | Update a child's data                          |
| DELETE | `/parent/children/{childId}` | Delete a child                                 |

**Details:**

- **List all children** — `GET /parent/children`: returns a short list of all children belonging to the currently logged-in parent. Typically used on the parent's home screen or a child-selection screen.
- **View full profile** — `GET /parent/children/{childId}`: returns all of the child's data in addition to all content linked to them, including basic content information and child statistics such as total points and number of stars.
- **Add a child** — `POST /parent/children`: creates a new child profile and automatically links it to the currently logged-in parent.
- **Update a child** — `POST /parent/children/{childId}`: updates the child's data. When a new profile picture is uploaded, the previous picture is automatically replaced.
- **Delete a child** — `DELETE /parent/children/{childId}`: deletes the child's profile along with all related data.

---

## 📚 Child Content Management

The parent submits content on behalf of the child. Content is created with status **Pending** until it is reviewed and approved by a supervisor/admin.

| Method | Endpoint                                         | Description                    |
| ------ | ------------------------------------------------ | ------------------------------ |
| POST   | `/parent/children/{childId}/content`             | Create new content for a child |
| POST   | `/parent/children/{childId}/content/{contentId}` | Update content                 |
| DELETE | `/parent/children/{childId}/content/{contentId}` | Delete content                 |

**Details:**

- **Create content** — `POST /parent/children/{childId}/content`: creates new content and links it to the child specified in the route.
- **Update content** — `POST /parent/children/{childId}/content/{contentId}`: updates the content data. Any edit resets the content status back to **Pending** for re-review.
- **Delete content** — `DELETE /parent/children/{childId}/content/{contentId}`: deletes the content and all of its related files.

### 🛠 Developer Notes — Children & Content

1. All routes in this section are available **only** to users with the **Parent** role.
2. The child is automatically linked to the logged-in parent, so `parent_id` is **not** sent in the request.
3. Content is automatically linked to the child specified via `childId` in the route.
4. Two records are created when new content is added:
    - A record in the `contents` table.
    - A record in the `child_contents` table linking the content to the child.
5. All content is created with status **Pending** and does not appear as approved content until reviewed by a supervisor/admin.
6. The child's profile picture and the content's cover image are optional; if not uploaded, a default image is returned automatically via the model accessor.
7. When updating a profile picture, cover image, or the content's main file, the old file is automatically deleted from storage and replaced with the new one.
8. The child's total points and number of stars are calculated dynamically based on the points awarded to content in the `child_contents` table.
9. The number of stars for each content item is calculated based on the `points_awarded` value.

---

## 🧒 Recovered Child Profile API

This section covers all endpoints related to the Recovered Child user profile, including profile data, content, sessions, invitations, and profile updates.

All routes are protected by:

```
auth:sanctum + check.role:recovered_child
```

**Base URL:**

```
/api/profile
```

### 📌 GET `/api/profile`

Returns full profile data, including:

- User information
- Profile details
- Awareness and motivational content (paginated)
- Joined sessions
- Invitations
- Statistics

### 📚 GET `/profile/all-contents`

Returns all educational content for the recovered child.

**Features:**

- Paginated (3 items per page)
- Sorted by latest
- Supports text and file content

### 🧠 GET `/profile/all-sessions`

Returns all sessions joined by the user.

**Features:**

- Paginated (3 items per page)
- Sorted by latest

### 📩 GET `/profile/all-invitations`

Returns all invitations **except** those with status `pending`.

This includes:

- `sent`
- `accepted`
- `declined`

**Features:**

- Paginated (3 items per page)
- Includes basic session info (title, `date_time`, `created_by` name)

### ✉️ POST `/profile/invitations/{invitationId}/handle`

Used to update an invitation's status or revert it (undo action).

This endpoint supports:

- Accepting an invitation
- Declining an invitation
- Reverting back to `sent` (cancel decision)

**Allowed statuses:** `sent` | `accepted` | `declined`

**Rules:**

- No decision is final — the user can change their response at any time.
- Status can be updated multiple times.
- Setting it back to `sent` means "undo response."

### 🧾 POST `/profile/update`

Updates recovered child profile information. All fields are optional and can be updated partially:

- `age`
- `recovery_duration`
- `cancer_type`
- `recovery_date`
- `location`
- `recovery_story`
- `profile_picture`

### 📊 Statistics (returned in the profile response)

- Total contents
- Total sessions
- Total activities
- Total impact score

### ⚠️ Notes

- Invitations with status `pending` are hidden.
- Invitations support reversible actions (no final state).
- Sessions are shown only if joined.
- All timestamps are returned in ISO format.

---

## 🧑‍⚕️ Specialist — Sessions & Activities

| Method | Endpoint                                  | Description                        |
| ------ | ----------------------------------------- | ---------------------------------- |
| GET    | `/specialist/sessions/recovered-children` | Get the list of recovered children |
| POST   | `/specialist/sessions`                    | Create a session or activity       |
| POST   | `/specialist/sessions/{id}`               | Update a session or activity       |
| DELETE | `/specialist/sessions/{id}`               | Delete a session or activity       |

---

## 📢 Awareness & Motivational Content

Accessible to **Specialist** and **Recovered Child** roles.

| Method | Endpoint                 | Description                              |
| ------ | ------------------------ | ---------------------------------------- |
| POST   | `/awareness`             | Create awareness or motivational content |
| POST   | `/awareness/{contentId}` | Update content                           |
| DELETE | `/awareness/{contentId}` | Delete content                           |

---

## 💰 Donor Campaigns

| Method | Endpoint                | Description       |
| ------ | ----------------------- | ----------------- |
| POST   | `/donor/campaigns`      | Create a campaign |
| POST   | `/donor/campaigns/{id}` | Update a campaign |
| DELETE | `/donor/campaigns/{id}` | Delete a campaign |

**Note:** When a campaign is edited:

- The data is updated.
- The status changes to `pending`.
- It is resubmitted for review.

---

## 📌 Content Listing APIs (Central Content Hub Routes)

These endpoints retrieve and filter different types of platform content with pagination support. All routes are protected by role-based middleware to ensure proper access control. Filtering and sorting are done using **query parameters** in the URL.

| Method | Endpoint          | Allowed Roles                       | Description                           |
| ------ | ----------------- | ----------------------------------- | ------------------------------------- |
| GET    | `/campaigns`      | Parent, Donor                       | List donation campaigns               |
| GET    | `/child-contents` | Parent, Recovered Child, Specialist | List child-generated content          |
| GET    | `/awareness`      | Parent, Recovered Child, Specialist | List awareness & motivational content |
| GET    | `/session`        | Parent, Recovered Child, Specialist | List sessions and activities          |

---

### 🎯 GET `/campaigns`

Returns donation campaigns that are visible to the public feed.

**Visibility rule:**

- Only campaigns whose status is **not** `pending` and **not** `rejected` are returned (i.e. `active`, `completed`, etc.).

**Sorting:** Latest first (`created_at DESC`).

**Pagination:** 4 items per page.

**Response shape:**

```json
{
    "success": true,
    "data": {
        /* paginated campaigns */
    }
}
```

**Example:**

```http
GET /api/campaigns
GET /api/campaigns?page=2
```

---

### 👶 GET `/child-contents`

Returns approved child-generated content (stories, drawings, etc.).

**Visibility rule:**

- Only content whose related `content.status` is `approved` is returned.

**Query Parameters**

| Parameter | Values                            | Description                                  |
| --------- | --------------------------------- | -------------------------------------------- |
| `type`    | e.g. `story`, `drawing`, `other`  | Filters results by `content_category_type`   |
| `sort`    | e.g. `latest`, `most_interactive` | Orders results by total engagement or latest |

**Sorting logic:**

- **Default** (no `sort` param): latest first (`created_at DESC`) or sort=`latest`.
- **`sort=most_interactive`**: joins the underlying `contents` table and orders by `(likes_count + comments_count) DESC`, i.e. the most-liked-and-commented content appears first.

**Pagination:** 6 items per page.

**Examples:**

```http
GET /api/child-contents
GET /api/child-contents?type=drawing
GET /api/child-contents?sort=most_interactive
GET /api/child-contents?type=story&sort=most_interactive
```

---

### 🧠 GET `/awareness`

Returns approved awareness & motivational content.

**Visibility rule:**

- Only content whose related `content.status` is `approved` is returned.

**Query Parameters**

| Parameter | Values                         | Description                                |
| --------- | ------------------------------ | ------------------------------------------ |
| `type`    | e.g. `article`, `video`, `tip` | Filters results by `content_category_type` |

**Sorting:** Latest first (`created_at DESC`).

**Pagination:** 6 items per page.

**Examples:**

```http
GET /api/awareness
GET /api/awareness?type=article
```

---

### 🎮 GET `/session`

Returns sessions and activities that are ready to be publicly displayed.

**Visibility rule:**

- Only items whose status is `approved`, `ongoing`, or `completed` are returned. Sessions still `pending` review are excluded.

**Query Parameters**

| Parameter | Values                  | Description                          |
| --------- | ----------------------- | ------------------------------------ |
| `type`    | `session` or `activity` | Filters results by the `type` column |

**Sorting:** Latest first, based on `date_time` (`date_time DESC`) — not `created_at`.

**Pagination:** 6 items per page.

**Examples:**

```http
GET /api/session
GET /api/session?type=activity
GET /api/session?type=session
```

---

### 🧩 Summary Table — Query Parameters by Endpoint

| Endpoint          | Supported Params                 | Default Sort      | Page Size |
| ----------------- | -------------------------------- | ----------------- | --------- |
| `/campaigns`      | — (status filter is automatic)   | `created_at DESC` | 4         |
| `/child-contents` | `type`, `sort=most_interactive`  | `created_at DESC` | 6         |
| `/awareness`      | `type`                           | `created_at DESC` | 6         |
| `/session`        | `type` (`session` \| `activity`) | `date_time DESC`  | 6         |

---

## 🖼️ Cover Image Logic

Applies to:

- Campaigns
- Sessions & activities
- Awareness & motivational content

### Uploading a Cover Image

The request must be sent as:

```
multipart/form-data
```

When an image is provided:

- It is stored in public storage.
- The path is saved in the database.
- The URL is returned as:

```json
{
    "cover_image": "image_url"
}
```

### Not Uploading an Image

If no image is sent:

- `NULL` is saved in the database.
- A default avatar is returned when displaying the item.

### Updating a Cover Image

When a new image is sent:

1. The old image is deleted.
2. The new image is uploaded.
3. The path is updated.

If no image is sent, the current image remains unchanged.

---

## 🔄 Auto Re-Review (Update) Logic

When editing items that require review, the data is updated and the status is automatically changed to:

```
pending
```

The item is then resubmitted for review by the administration.

---

## 🚫 Operation Restrictions

### Campaigns

Editing a completed campaign is **not** allowed:

```
status = completed
```

Response: `403 Forbidden`

### Sessions

Editing or deleting a session is **not** allowed if its status is:

```
ongoing
```

or

```
completed
```

Response: `403 Forbidden`

### Rejected Accounts

If the account status is:

```
rejected
```

Access is denied and the rejection reason entered by the admin is returned.

---

## ⭐ Interaction System (Likes & Comments)

This module handles all user interactions across the platform, including likes, comments, and retrieving interaction data, using a polymorphic structure.

**Base route:** all interaction endpoints are prefixed with `/api/interact`.

### 👍 Like System

**Toggle Like**

```
POST /api/interact/{type}/{id}/like
```

**Supported types:**

- `content`
- `child_content`
- `awareness_content`
- `campaign`
- `session`

**General rules:**

- The like system is a toggle (like / unlike).
- Uses polymorphic relationships.
- Each user can like a content item only once per context.
- The system validates role-based permissions before storing the interaction.

### 💬 Comment System

**Add Comment**

```
POST /api/interact/{type}/{id}/comment
```

**Rules:**

- Only authenticated users can comment.
- Children are **not** allowed to comment under any condition.
- Comments are linked to polymorphic content.
- Access depends on user role and content type permissions.

**Get Comments**

```
GET /api/interact/{type}/{id}/comments
```

**Response includes:**

- Commenter name
- Comment text
- Timestamp
- Total number of comments

### 👨‍👩‍👧 Parent Interaction Options (Child Content Only)

**Get Parent & Children Like Status**

```
GET /api/interact/{type}/{id}/options
```

**Purpose:** used for `child_content` to display:

- The parent's like status
- Each child's like status under the same content

**Access:**

- Only Parent users
- Only works with `child_content`

### 🔐 Content Permissions (Interaction Rules)

**👶 `child_content`**

- **Like:** Parent, Specialist, Recovered Child, Child
- **Comment:** Parent, Specialist, Recovered Child
- ❌ Donor: No access (like/comment)
- ⚠️ Child: Can LIKE only (cannot comment)

**🧠 `awareness_content`**

- **Like:** Parent, Specialist, Recovered Child, Child
- **Comment:** Parent, Specialist, Recovered Child, Child
- ❌ Donor: No access (like/comment)

**🎯 `campaign`**

- **Like:** Parent, Donor
- **Comment:** Parent, Donor

**🎮 `session`**

- **Like:** Parent, Specialist, Recovered Child, Child
- **Comment:** Parent, Specialist, Recovered Child, Child
- ❌ Donor: No access (like/comment)

### ⚙️ Business Rules Summary

- Likes & comments use polymorphic relationships.
- `child_content` supports a parent-child interaction model.
- `awareness_content` is treated as standard content.
- Donor has a limited interaction scope (campaign only).
- Child users cannot comment.
- Role-based validation is enforced on the backend.
- All timestamps are formatted for frontend usage.

### 🚀 Status

- ✔ Likes system completed
- ✔ Comments system completed
- ✔ Parent-child interaction supported
- ✔ Role-based permissions implemented
- ✔ API fully ready for frontend integration

---

## 📊 Admin Dashboard API

Provides aggregated statistics, community growth metrics, interaction analytics, and a tab-based filtered listing of platform data for the admin dashboard.

**Access:** Admin only
**Middleware:** `check.role:admin`
**Base route:** `/admin/dashboard`

### 📌 GET `/admin/dashboard`

Returns dashboard analytics including:

- Platform statistics
- Community growth metrics
- Interaction chart data (last 6 months)
- Filtered content list based on the selected tab

### 📊 Response Sections

**1. Statistics (`stats`)**

| Field                  | Description                                 |
| ---------------------- | ------------------------------------------- |
| `content_and_sessions` | Total awareness content + activity sessions |
| `child_profiles`       | Number of child recovery profiles           |
| `donation_campaigns`   | Total donation campaigns                    |
| `parent_users`         | Total parent accounts                       |

**2. Community Growth (`community_growth`)**

- Percentage change of user growth between the current and previous month.
- Returned as a numeric value (can be positive, zero, or negative).
- Used for dashboard progress indicators.

**3. Chart Data (`chart_data`)**

- Shows interaction trends (likes + comments).
- Covers the last 6 months (fixed range).
- Each item contains:

```json
{
    "month": "2026-07",
    "total_interaction": 30
}
```

### 🗂️ Tab Query Filter (`tab`)

**Usage:**

```
GET /admin/dashboard?tab={value}
```

**Available values:**

| Value           | Description                                                        | Type                | Label                            |
| --------------- | ------------------------------------------------------------------ | ------------------- | -------------------------------- |
| `all` (default) | Returns all platform data combined (contents, campaigns, sessions) | —                   | —                                |
| `campaigns`     | Donation campaigns only                                            | `campaign`          | Donation Campaigns               |
| `sessions`      | Activity sessions only                                             | `session`           | Sessions & Activities            |
| `child_content` | Child-generated content only (stories, drawings, other)            | `child_content`     | Children's Content               |
| `awareness`     | Awareness & motivational content only                              | `awareness_content` | Awareness & Motivational Content |

**Examples:**

```http
GET /api/admin/dashboard
GET /api/admin/dashboard?tab=campaigns
GET /api/admin/dashboard?tab=sessions
GET /api/admin/dashboard?tab=child_content
GET /api/admin/dashboard?tab=awareness
```

### 📌 Notes

- All results are paginated (4 items per page).
- Default sorting: `created_at DESC`.
- Each item includes:
    - `id`
    - `title`
    - `status`
    - `created_at`
    - `type`
    - `category_label`

### 🧠 Summary

The `tab` query enables dynamic filtering of dashboard data without changing the endpoint: campaigns, sessions, child content, awareness content, or a combined view (`all`).

---

## 🛡 Admin Content Moderation (Actions)

These endpoints are available only to **Admin** users and are used to review, moderate, and manage platform content.

### 👁 View Content Details

- **GET** `/admin/actions/show/{type}/{id}`
- **Access:** Admin
- **Description:** Retrieves the complete details of a specific item before taking an action.

**Supported types:** `child_content`, `awareness_content`, `campaign`, `session`

### 📋 Get Child Content Meta (Approval Popup Data)

- **GET** `/admin/actions/meta/child-content/{id}`
- **Middleware:** `auth:sanctum` + `check.role:admin`
- **Description:** Returns lightweight data for the approval UI (popup only).

**Response example:**

```json
{
    "title": "رسمة جميلة",
    "child_name": "أحمد"
}
```

**Used for:**

- Approval popup display
- Showing the content title
- Showing the child's name
- Preparing the points input UI (handled by the frontend)

### ✅ Approve Content

- **POST** `/admin/actions/approve/{type}/{id}`
- **Access:** Admin
- **Description:** Approves the selected item.

**Supported types:** `child_content`, `awareness_content`, `campaign`, `session`

**Special behavior:**

- **Child Content**
    - Requires the `points` field.
    - Awards points to the child after approval.
- **Sessions**
    - Automatically sends invitations to invited recovered children.
- **Campaigns**
    - Status changes to `active`.
- **Awareness Content**
    - Status changes to `approved`.

**Optional body** (only when approving `child_content`):

```json
{
    "points": 15
}
```

> `points` is accepted **only** for `child_content`. Sending it with any other content type returns a validation error.

### ❌ Reject Content

- **POST** `/admin/actions/reject/{type}/{id}`
- **Access:** Admin
- **Description:** Rejects the selected item.

**Supported types:** `child_content`, `awareness_content`, `campaign`, `session`

### 🔐 Access Control Summary

- **Admin:** Full access to the dashboard and moderation APIs.
- **Other roles:** No access.

---

## 👥 Admin — User Management

These endpoints are used by the **Admin** to manage platform users, including listing, searching, and toggling account status (activate / deactivate).

All routes are protected by: `check.role:admin`

### 📄 Get Users List

- **GET** `/admin/users`
- **Access:** Admin only
- **Description:** Retrieves a paginated list of users (excluding rejected accounts) with optional search.

**Query Parameters**

| Parameter | Type   | Description                  |
| --------- | ------ | ---------------------------- |
| `search`  | string | Search by full name or email |
| `page`    | int    | Pagination page number       |

### 🔁 Toggle User Status

- **PATCH** `/admin/users/{id}/toggle-status`
- **Access:** Admin only
- **Description:** Activates or deactivates a user account.

---

## ⚙️ System Settings (Public & Admin)

### 1. Get System Settings

Retrieves all system-wide settings (accessible to all users, including guests).

- **URL:** `/api/system/settings`
- **Method:** `GET`
- **Middleware:** None
- **Description:** Returns all system configuration settings in key-value format.

**Response example:**

```json
{
    "settings": {
        "phone": "+970 59 123 4567",
        "support_email": "healingjourney.support@gmail.com",
        "address": "رام الله - فلسطين",
        "facebook_url": "https://facebook.com/healingjourney",
        "instagram_url": "https://instagram.com/healingjourney",
        "linkedin_url": "https://linkedin.com/company/healingjourney",
        "footer_text": "© ٢٠٢٦ – رحلة شفاء. جميع الحقوق محفوظة"
    }
}
```

### 2. Update System Settings

Updates system-wide settings (Admin only).

- **URL:** `/api/admin/system/settings/update`
- **Method:** `POST`
- **Middleware:** `auth:sanctum` + `admin`
- **Content-Type:** `application/json`
- **Description:** Allows the admin to update system settings using key-value pairs. Each key is automatically created or updated in the database.

**Request example:**

```json
{
    "phone": "+970 59 777 7777",
    "support_email": "support@healingjourney.com",
    "address": "Nablus - Palestine",
    "facebook_url": "https://facebook.com/newpage",
    "instagram_url": "https://instagram.com/newpage",
    "linkedin_url": "https://linkedin.com/company/newpage",
    "footer_text": "© 2026 Healing Journey. All rights reserved"
}
```

**Response example:**

```json
{
    "message": "تم تحديث إعدادات المنصة بنجاح"
}
```

---

## 🗂️ Categories API

All routes are for **Admin only** and are protected by `auth:sanctum`.

### 1. Get All Categories

- **URL:** `/api/admin/categories`
- **Method:** `GET`
- **Middleware:** `auth:sanctum`
- **Description:** Retrieves all categories in descending order, with pagination (3 items per page).

**Pagination example:**

```
/api/admin/categories?page=2
```

### 2. Create Category

- **URL:** `/api/admin/categories`
- **Method:** `POST`
- **Middleware:** `auth:sanctum`
- **Content-Type:** `application/json`

**Parameters:**

- `name` (string, required)
- `description` (string, optional)
- `is_active` (boolean, optional)

**Notes:** The `slug` is generated automatically from the name.

### 3. Update Category

- **URL:** `/api/admin/categories/{id}`
- **Method:** `PUT`
- **Middleware:** `auth:sanctum`

**Parameters:**

- `name` (string, optional)
- `description` (string, optional)
- `is_active` (boolean, optional)

**Notes:** If the name is edited, the `slug` is updated automatically.

### 4. Toggle Category Status

- **URL:** `/api/admin/categories/{id}/toggle-status`
- **Method:** `PATCH`
- **Middleware:** `auth:sanctum`

**Description:** Toggles the category status: `true` → `false` or `false` → `true`.

**Response:**

```json
{
    "message": "تم تغيير حالة التصنيف",
    "is_active": true
}
```

### 5. Delete Category

- **URL:** `/api/admin/categories/{id}`
- **Method:** `DELETE`
- **Middleware:** `auth:sanctum`
- **Description:** Permanently deletes a category from the system.

**Notes:**

- All routes in this section are for Admin only.
- `paginate(3)` is used for displaying data.
- You can navigate between pages using `?page=1`, `?page=2`, `?page=3`, etc.

---

## 📩 Contact Messages API

This section covers the API endpoints for handling contact messages in the system. It includes both **public (user/visitor)** routes and **admin-only** routes.

### 🔹 1. Send Contact Message (Public)

Allows users or visitors to send a message.

- **URL:** `/api/contact`
- **Method:** `POST`
- **Middleware:** None (public access)
- **Content-Type:** `application/json`

**Request body:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "subject": "Support Request",
    "message": "I need help with my account"
}
```

**Response:**

```json
{
    "success": true,
    "message": "تم استلام رسالتك بنجاح وسنتواصل معك قريباً!"
}
```

### 🔹 2. Get Contact Messages (Admin)

Fetches all messages with filtering and pagination.

- **URL:** `/api/admin/messages`
- **Method:** `GET`
- **Middleware:** `auth:sanctum` + `admin`
- **Pagination:** 3 messages per page

**Query Parameters (Filtering)**

| Parameter | Value     | Description                                   |
| --------- | --------- | --------------------------------------------- |
| `status`  | `new`     | Messages from the last 24 hours & not replied |
| `status`  | `pending` | All messages not replied (old + new)          |
| `status`  | `replied` | Only replied messages                         |

**Example requests:**

```
GET /api/admin/messages
GET /api/admin/messages?status=new
GET /api/admin/messages?status=pending
GET /api/admin/messages?status=replied
GET /api/admin/messages?page=2
```

### 🔹 3. Reply to Message (Admin Only)

Sends a reply and marks the message as replied.

- **URL:** `/api/admin/messages/{id}/reply`
- **Method:** `POST`
- **Middleware:** `auth:sanctum` + `admin`

**Request body:**

```json
{
    "reply_text": "Thank you for contacting us. We solved your issue."
}
```

**Response:**

```json
{
    "success": true,
    "message": "تم حفظ الرد وتغيير الحالة بنجاح"
}
```

**📌 Notes (important logic):**

- `new` → Messages created within the last 24 hours **and** not replied.
- `pending` → All messages not replied (includes new + old).
- `replied` → Messages with status `replied`.
- Pagination is controlled using `?page=1`, `?page=2`, etc.
- Default page size = 3 messages per request.

---

## 🚪 Logout (Reference)

```
POST /logout
```

**Behavior:**

- Deletes the user's active token.
- Logs the user out completely.

**After logout:**

- The token becomes invalid immediately.
- Any further request with the same token returns `401 Unauthorized`.

**🔐 Token behavior after logout:**

- Token is immediately invalidated.
- Cannot be reused.
- Requires re-login.

---

## 🛠 Developer Notes

1. Additional fields such as `bio`, `specialty`, `recovery_story`, etc. are **not** part of the registration process — they are filled in later through a profile update.
2. When using document routes, replace `{type}` with one of the following values: `identity_card`, `certificate`.
3. Most routes accept files, so requests must be sent as `multipart/form-data`.
4. **Update method:** `POST` requests are used when updating data or uploading files. There is no need to send `_method: PUT`, since update routes are configured to accept `POST` directly.
5. Accounts without an uploaded picture use a default avatar from `ui-avatars.com`, while accounts with an uploaded picture display the real image.
6. Any edit to sessions or content automatically changes the status to `pending` and requires re-approval from the administration.
7. When updating `recovered_child_ids`, the system automatically removes canceled invitations and adds only the new invitations.
8. When testing the API via Postman, make sure to send `Authorization: Bearer {token}`.

---

_Documented by the Healing Journey development team — 2026_
