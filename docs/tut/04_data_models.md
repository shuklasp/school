# 04. Data Modeling with YAML Entities

Databases in SPP are handled using a declarative ORM-like system. Instead of writing SQL for table creation and management, you describe your models in **YAML** files.

---

## Defining an Entity

Entities are stored in `etc/entities/`. Let's look at a sample `student.yml`:

```yaml
table: students
id_field: id
sequence: students_seq
login_enabled: false
attributes:
  name: varchar(200)
  roll_no: int
  email: varchar(150)
  created_at: timestamp
relations:
  - parent_entity: Department
    parent_entity_field: dept_id
    child_entity_field: id
    relation_type: ManyToOne
```

### Key Fields:
-   **`table`**: The MySQL/PostgreSQL table name.
-   **`id_field`**: The primary key field (defaults to `id`).
-   **`sequence`**: The sequence used for ID generation.
-   **`attributes`**: Name-datatype pairs for table columns.
-   **`relations`**: Defines relationships with other entities (OneToOne, OneToMany, ManyToOne).

---

## Using the `SPPEntity` Class

Once defined, you can interact with your data using the `\SPPMod\SPPEntity\SPPEntity` base class.

### 1. Create a New Record
```php
$student = new \SPPMod\SPPEntity\SPPEntity();
$student->set('name', 'John Doe');
$student->set('roll_no', 101);
$student->save();
```

### 2. Load an Existing Record
```php
$student = new \SPPMod\SPPEntity\SPPEntity(1); // Load by ID
echo $student->get('name');
```

### 3. Search for Records
```php
$allStudents = (new \SPPMod\SPPEntity\SPPEntity())->loadAll();
foreach ($allStudents as $s) {
    echo $s->get('name');
}
```

---

## Automated Schema Management

One of SPP's most powerful features is **Automated Installation**. When you instantiate an `SPPEntity`, the framework automatically:
1.  Checks if the table exists in the database.
2.  Creates the table if missing.
3.  Adds any missing columns defined in the YAML.

This means you can sync your database schema across environments simply by updating your YAML files.

---

## The `SPPDB` Helper

If you need to perform raw SQL queries, use the `SPPDB` class:

```php
$db = new \SPPMod\SPPDB\SPPDB();
$sql = "SELECT * FROM %tab% WHERE status = ?";
$results = $db->exec_squery($sql, 'students', ['active']);
```
-   **`%tab%`**: Use this placeholder for table names to maintain portability.
-   **`exec_squery`**: Executes a secure, parameterized query.

---

[**Next: Routing and Views**](05_routing_and_views.md)
