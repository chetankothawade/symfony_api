INSERT INTO `users` (
  `uuid`,
  `name`,
  `email`,
  `phone`,
  `password`,
  `status`,
  `role`,
  `last_login_at`,
  `last_login_ip`,
  `last_login_ua`,
  `last_logout_at`,
  `created_at`,
  `updated_at`,
  `deleted_at`
) VALUES
  (UUID(), 'Super Admin', 'superadmin@example.com', NULL, '$2y$10$.7e0yJdMjqcwXNKGYwctw.eVNDb3gOcSTusnpAafXfPsaLeWYtMH6', 'active', 'super_admin', NULL, NULL, NULL, NULL, NOW(), NOW(), NULL),
  (UUID(), 'Admin', 'admin@example.com', NULL, '$2y$10$.7e0yJdMjqcwXNKGYwctw.eVNDb3gOcSTusnpAafXfPsaLeWYtMH6', 'active', 'admin', NULL, NULL, NULL, NULL, NOW(), NOW(), NULL),
  (UUID(), 'Editor', 'editor@example.com', NULL, '$2y$10$.7e0yJdMjqcwXNKGYwctw.eVNDb3gOcSTusnpAafXfPsaLeWYtMH6', 'active', 'editor', NULL, NULL, NULL, NULL, NOW(), NOW(), NULL);
