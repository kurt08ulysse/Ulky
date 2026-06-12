import os, paramiko

password = os.environ['VM_PWD']
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('127.0.0.1', port=2222, username='bradley', password=password,
               timeout=10, allow_agent=False, look_for_keys=False)

# Use tinker via php artisan
PHP = r'''
$users = \App\Models\User::all(["id","clerk_id","name","email","commune_id"]);
echo "USERS COUNT: ".$users->count().PHP_EOL;
foreach ($users as $u) {
    echo "  [".$u->id."] clerk_id=".$u->clerk_id." | name=".$u->name." | email=".$u->email." | commune_id=".($u->commune_id ?? "null").PHP_EOL;
    $roles = $u->getRoleNames()->toArray();
    echo "      roles: ".(empty($roles) ? "(none)" : implode(",", $roles)).PHP_EOL;
    $count = \App\Models\TaxNotice::where("user_id",$u->id)->count();
    echo "      tax_notices: ".$count.PHP_EOL;
}
echo PHP_EOL."TAXES (référentiel) COUNT: ".\App\Models\Tax::count().PHP_EOL;
echo "First 5 taxes:".PHP_EOL;
foreach (\App\Models\Tax::take(5)->get() as $t) {
    echo "  [".$t->id."] ".$t->name." | base=".$t->base_amount." | stamp=".$t->stamp_amount.PHP_EOL;
}
echo PHP_EOL."TAX_NOTICES TOTAL: ".\App\Models\TaxNotice::count().PHP_EOL;
'''

cmd = f'cd /var/www/ulky/backend && php artisan tinker --execute={PHP!r}'
_, stdout, stderr = client.exec_command(cmd, timeout=60)
rc = stdout.channel.recv_exit_status()
print(stdout.read().decode())
err = stderr.read().decode().rstrip()
if err: print('[stderr]', err)
print(f'[rc={rc}]')
client.close()
