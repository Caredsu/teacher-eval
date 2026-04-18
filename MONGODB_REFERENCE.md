# Database Schema & MongoDB Operations Reference

## Collection Schemas

### users
**Purpose:** Store admin and staff accounts with authentication credentials

**Schema:**
```json
{
  "_id": ObjectId,
  "username": "string (unique)",
  "password_hashed": "string (bcrypt hash)",
  "role": "string (superadmin|staff)",
  "created_at": ISODate
}
```

**Indexes:**
```
db.users.createIndex({ "username": 1 }, { "unique": true })
```

**Sample Query:**
```javascript
db.users.findOne({ username: "admin" })
```

---

### teachers
**Purpose:** Store teacher information and department assignment

**Schema:**
```json
{
  "_id": ObjectId,
  "firstname": "string",
  "lastname": "string",
  "middlename": "string",
  "department": "string (ECT|EDUC|CCJE|BHT)",
  "created_at": ISODate,
  "updated_at": ISODate (optional)
}
```

**Indexes:**
```
db.teachers.createIndex({ "department": 1 })
db.teachers.createIndex({ "lastname": 1, "firstname": 1 }) // Optional for search
```

**Sample Queries:**
```javascript
// Get all teachers
db.teachers.find({})

// Get teachers by department
db.teachers.find({ department: "ECT" })

// Find teacher by name
db.teachers.findOne({ firstname: "John", lastname: "Smith" })

// Update teacher
db.teachers.updateOne(
  { _id: ObjectId("...") },
  { $set: { middlename: "Michael", updated_at: new Date() } }
)

// Delete teacher
db.teachers.deleteOne({ _id: ObjectId("...") })
```

---

### evaluations
**Purpose:** Store student evaluations with ratings and feedback

**Schema:**
```json
{
  "_id": ObjectId,
  "teacher_id": ObjectId,
  "ratings": {
    "teaching": "number (1-5)",
    "communication": "number (1-5)",
    "knowledge": "number (1-5)"
  },
  "feedback": "string (10-1000 chars)",
  "session_identifier": "string (IP address)",
  "submitted_at": ISODate
}
```

**Indexes:**
```
db.evaluations.createIndex({ "teacher_id": 1 })
db.evaluations.createIndex({ "submitted_at": 1 })
db.evaluations.createIndex({ "session_identifier": 1, "teacher_id": 1, "submitted_at": 1 })
```

**Sample Queries:**
```javascript
// Get all evaluations for a teacher
db.evaluations.find({ teacher_id: ObjectId("...") })

// Calculate average ratings for a teacher
db.evaluations.aggregate([
  {
    $match: { teacher_id: ObjectId("...") }
  },
  {
    $group: {
      _id: "$teacher_id",
      count: { $sum: 1 },
      avg_teaching: { $avg: "$ratings.teaching" },
      avg_communication: { $avg: "$ratings.communication" },
      avg_knowledge: { $avg: "$ratings.knowledge" }
    }
  }
])

// Find recent evaluations (last 7 days)
db.evaluations.find({
  submitted_at: { $gte: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000) }
})

// Check for duplicate submission (last hour from same IP)
db.evaluations.findOne({
  teacher_id: ObjectId("..."),
  session_identifier: "192.168.1.100",
  submitted_at: { $gte: new Date(Date.now() - 60 * 60 * 1000) }
})

// Get top-rated teachers
db.evaluations.aggregate([
  {
    $group: {
      _id: "$teacher_id",
      avg_rating: { $avg: { $avg: ["$ratings.teaching", "$ratings.communication", "$ratings.knowledge"] } }
    }
  },
  { $sort: { avg_rating: -1 } },
  { $limit: 10 }
])

// Get teachers with most evaluations
db.evaluations.aggregate([
  {
    $group: {
      _id: "$teacher_id",
      count: { $sum: 1 }
    }
  },
  { $sort: { count: -1 } }
])
```

---

### departments (optional)
**Purpose:** Reference table for department information

**Schema:**
```json
{
  "_id": ObjectId,
  "code": "string (ECT|EDUC|CCJE|BHT)",
  "name": "string",
  "description": "string (optional)"
}
```

**Sample Documents:**
```javascript
[
  { code: "ECT", name: "Education and Communication Technologies", description: "..." },
  { code: "EDUC", name: "Education", description: "..." },
  { code: "CCJE", name: "Criminal Justice and Education", description: "..." },
  { code: "BHT", name: "Business and Hospitality", description: "..." }
]
```

---

## Advanced MongoDB Operations

### Aggregation: Get Teacher Statistics

```javascript
// Detailed statistics for all teachers
db.teachers.aggregate([
  {
    $lookup: {
      from: "evaluations",
      localField: "_id",
      foreignField: "teacher_id",
      as: "evaluations"
    }
  },
  {
    $project: {
      firstname: 1,
      lastname: 1,
      department: 1,
      total_evaluations: { $size: "$evaluations" },
      avg_teaching: {
        $cond: [
          { $gt: [{ $size: "$evaluations" }, 0] },
          { $avg: "$evaluations.ratings.teaching" },
          null
        ]
      },
      avg_communication: {
        $cond: [
          { $gt: [{ $size: "$evaluations" }, 0] },
          { $avg: "$evaluations.ratings.communication" },
          null
        ]
      },
      avg_knowledge: {
        $cond: [
          { $gt: [{ $size: "$evaluations" }, 0] },
          { $avg: "$evaluations.ratings.knowledge" },
          null
        ]
      }
    }
  }
])
```

### Aggregation: Department Performance Report

```javascript
// Performance metrics by department
db.teachers.aggregate([
  {
    $lookup: {
      from: "evaluations",
      localField: "_id",
      foreignField: "teacher_id",
      as: "evaluations"
    }
  },
  {
    $unwind: "$evaluations"
  },
  {
    $group: {
      _id: "$department",
      total_teachers: { $push: "$_id" },
      total_evaluations: { $sum: 1 },
      avg_teaching: { $avg: "$evaluations.ratings.teaching" },
      avg_communication: { $avg: "$evaluations.ratings.communication" },
      avg_knowledge: { $avg: "$evaluations.ratings.knowledge" }
    }
  },
  {
    $project: {
      department: "$_id",
      _id: 0,
      unique_teachers: { $size: { $setUnion: ["$total_teachers"] } },
      total_evaluations: 1,
      avg_teaching: { $round: ["$avg_teaching", 2] },
      avg_communication: { $round: ["$avg_communication", 2] },
      avg_knowledge: { $round: ["$avg_knowledge", 2] },
      overall_avg: {
        $round: [
          {
            $avg: [
              "$avg_teaching",
              "$avg_communication",
              "$avg_knowledge"
            ]
          },
          2
        ]
      }
    }
  },
  { $sort: { overall_avg: -1 } }
])
```

### Maintenance: Delete Old Evaluations

```javascript
// Delete evaluations older than 6 months
db.evaluations.deleteMany({
  submitted_at: { $lt: new Date(Date.now() - 180 * 24 * 60 * 60 * 1000) }
})
```

### Maintenance: Update All Teachers

```javascript
// Add field to all documents
db.teachers.updateMany(
  {},
  {
    $set: { last_updated: new Date() },
    $currentDate: { updated_at: true }
  }
)
```

### Search: Find Teachers by Partial Name

```javascript
// Create text index (optional for better search)
db.teachers.createIndex({ firstname: "text", lastname: "text" })

// Search
db.teachers.find({
  $text: { $search: "john" }
})

// Or use regex
db.teachers.find({
  $or: [
    { firstname: /john/i },
    { lastname: /smith/i }
  ]
})
```

---

## MongoDB Backup & Restore

### Backup
```bash
# Backup entire database
mongodump --db teacher_evaluation --out ./backup_dir

# Backup specific collection
mongodump --db teacher_evaluation --collection teachers --out ./backup_dir

# Backup with compression
mongodump --db teacher_evaluation --archive=backup.archive --gzip
```

### Restore
```bash
# Restore entire database
mongorestore --db teacher_evaluation ./backup_dir/teacher_evaluation

# Restore from compressed archive
mongorestore --archive=backup.archive --gzip
```

### Export to CSV (for reports)
```bash
# Export evaluations
mongoexport --db teacher_evaluation --collection evaluations --out evaluations.csv --csv --fields "_id,teacher_id,ratings.teaching,ratings.communication,ratings.knowledge,feedback,submitted_at"
```

---

## Data Validation Rules

### users Collection
- `username`: 3-50 chars, alphanumeric + underscore, unique
- `password_hashed`: bcrypt hash, minimum 60 chars
- `role`: must be "superadmin" or "staff"

### teachers Collection
- `firstname`: 2-50 chars, letters only
- `lastname`: 2-50 chars, letters only
- `middlename`: optional, 2-50 chars, letters only
- `department`: must be one of: ECT, EDUC, CCJE, BHT
- `created_at`, `updated_at`: ISO date format

### evaluations Collection
- `teacher_id`: valid ObjectId, must exist in teachers collection
- `ratings.teaching`: integer 1-5
- `ratings.communication`: integer 1-5
- `ratings.knowledge`: integer 1-5
- `feedback`: 10-1000 chars, any content
- `session_identifier`: IP address format
- `submitted_at`: ISO date format
- Duplicate prevention: One evaluation per IP per teacher per hour

---

## Notes for Developers

1. **Connection Pooling:** MongoDB PHP driver uses connection pooling by default
2. **Transactions:** Not used in single-document operations, but available for multi-document
3. **Text Search:** Currently using simple queries, can be enhanced with text indexes
4. **Caching:** Consider Redis for frequently accessed data (e.g., departments)
5. **Replication:** Setup MongoDB replica sets for high availability
6. **Sharding:** Consider for large datasets (millions of evaluations)
7. **TTL Indexes:** Can auto-delete old evaluations using TTL indexes

---

## Useful MongoDB Commands

```javascript
// Connect to database
mongosh --host localhost --port 27017 --db teacher_evaluation

// Show all collections
show collections

// Show total size
db.stats()

// Show collection stats
db.teachers.stats()

// Count documents
db.teachers.countDocuments()

// Delete all documents
db.teachers.deleteMany({})

// Create backup
db.fsyncLock()
// ... copy data files ...
db.fsyncUnlock()

// Get indexes
db.evaluations.getIndexes()

// Drop index
db.evaluations.dropIndex("index_name")

// Rebuild all indexes
db.teachers.reIndex()
```
