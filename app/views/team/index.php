<div class="container">
    <h1>Team Members</h1>

    <?php if (!empty($users)): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
            </tr>

            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['role']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No team members found.</p>
    <?php endif; ?>
</div>