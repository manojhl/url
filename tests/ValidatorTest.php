<?php

use Crwlr\Url\Validator;
use PHPUnit\Framework\Assert;

/** @var \PHPUnit\Framework\TestCase $this */

test('ValidateUrl', function () {
    $this->assertEquals(
        'https://www.crwlr.software/packages/url/v0.1.2#installation',
        Validator::url('https://www.crwlr.software/packages/url/v0.1.2#installation')
    );
});

test('ValidateUrlWithSpecialCharacters', function () {
    $this->assertEquals(
        'https://u%C2%A7er:p%C3%A1ssword@sub.xn--domin-ira.example.org:345' .
        '/f%C3%B6%C3%B4/b%C3%A1r?qu%C3%A4r.y=str%C3%AFng#fr%C3%A4gm%C3%A4nt',
        Validator::url('https://u§er:pássword@sub.domäin.example.org:345/föô/bár?quär.y=strïng#frägmänt')
    );
});

test('ValidateInvalidUrl', function () {
    $this->assertNull(Validator::url('1http://example.com/stuff'));
    $this->assertNull(Validator::url('  https://wwww.example.com  '));
    $this->assertNull(Validator::url('http://'));
    $this->assertNull(Validator::url('http://.'));
    $this->assertNull(Validator::url('https://..'));
    $this->assertNull(Validator::url('https://../'));
    $this->assertNull(Validator::url('http://?'));
    $this->assertNull(Validator::url('http://#'));
    $this->assertNull(Validator::url('//'));
    $this->assertNull(Validator::url('///foo'));
    $this->assertNull(Validator::url('http:///foo'));
    $this->assertNull(Validator::url('://'));
});

test('ValidateUrlAndComponents', function () {
    assertArrayContains(
        Validator::urlAndComponents('https://www.crwlr.software/packages/url/v0.1.2#installation'),
        [
            'url' => 'https://www.crwlr.software/packages/url/v0.1.2#installation',
            'scheme' => 'https',
            'host' => 'www.crwlr.software',
            'path' => '/packages/url/v0.1.2',
            'fragment' => 'installation',
        ]
    );

    assertArrayContains(
        Validator::urlAndComponents('/foo/bar?query=string#fragment'),
        [
            'url' => '/foo/bar?query=string#fragment',
            'path' => '/foo/bar',
            'query' => 'query=string',
            'fragment' => 'fragment',
        ]
    );

    assertArrayContains(
        Validator::urlAndComponents('ftp://username:password@example.org'),
        [
            'url' => 'ftp://username:password@example.org',
            'scheme' => 'ftp',
            'user' => 'username',
            'pass' => 'password',
            'host' => 'example.org',
        ]
    );

    assertArrayContains(
        Validator::urlAndComponents('mailto:you@example.com?subject=crwlr software'),
        ['url' => 'mailto:you@example.com?subject=crwlr%20software']
    );
});

test('ValidateIdnUrlAndComponents', function () {
    assertArrayContains(
        Validator::urlAndComponents('http://✪df.ws/123'),
        [
            'url' => 'http://xn--df-oiy.ws/123',
            'scheme' => 'http',
            'host' => 'xn--df-oiy.ws',
            'path' => '/123',
        ]
    );

    assertArrayContains(
        Validator::urlAndComponents('https://www.example.онлайн/stuff'),
        [
            'url' => 'https://www.example.xn--80asehdb/stuff',
            'scheme' => 'https',
            'host' => 'www.example.xn--80asehdb',
            'path' => '/stuff',
        ]
    );
});

test('ValidateInvalidUrlAndComponents', function () {
    $this->assertNull(Validator::urlAndComponents('1http://example.com/stuff'));
    $this->assertNull(Validator::urlAndComponents('  https://wwww.example.com  '));
    $this->assertNull(Validator::urlAndComponents('http://'));
    $this->assertNull(Validator::urlAndComponents('http://.'));
    $this->assertNull(Validator::urlAndComponents('https://..'));
    $this->assertNull(Validator::urlAndComponents('https://../'));
    $this->assertNull(Validator::urlAndComponents('http://?'));
    $this->assertNull(Validator::urlAndComponents('http://#'));
    $this->assertNull(Validator::urlAndComponents('//'));
    $this->assertNull(Validator::urlAndComponents('///foo'));
    $this->assertNull(Validator::urlAndComponents('http:///foo'));
    $this->assertNull(Validator::urlAndComponents('://'));
});

test('ValidateAbsoluteUrl', function () {
    $this->assertEquals(
        'https://www.crwlr.software/packages/url/v0.1.2#installation',
        Validator::absoluteUrl('https://www.crwlr.software/packages/url/v0.1.2#installation')
    );

    $this->assertNull(Validator::absoluteUrl('/foo/bar?query=string#fragment'));
});

test('ValidateAbsoluteUrlAndComponents', function () {
    assertArrayContains(
        Validator::absoluteUrlAndComponents('https://www.crwlr.software/packages/url/v0.1.2#installation'),
        [
            'url' => 'https://www.crwlr.software/packages/url/v0.1.2#installation',
            'scheme' => 'https',
            'host' => 'www.crwlr.software',
            'path' => '/packages/url/v0.1.2',
            'fragment' => 'installation',
        ]
    );

    $this->assertNull(Validator::absoluteUrlAndComponents('/foo/bar?query=string#fragment'));
});

test('ValidateScheme', function () {
    $this->assertEquals('http', Validator::scheme('http'));
    $this->assertEquals('mailto', Validator::scheme('mailto'));
    $this->assertEquals('ssh', Validator::scheme('ssh'));
    $this->assertEquals('ftp', Validator::scheme('ftp'));
    $this->assertEquals('sftp', Validator::scheme('sftp'));
    $this->assertEquals('wss', Validator::scheme('wss'));
    $this->assertEquals('https', Validator::scheme('HTTPS'));

    $this->assertNull(Validator::scheme('1invalidscheme'));
    $this->assertNull(Validator::scheme('mäilto'));
});

test('ValidateAuthority', function () {
    $this->assertEquals('12.34.56.78', Validator::authority('12.34.56.78'));
    $this->assertEquals('localhost', Validator::authority('localhost'));
    $this->assertEquals('www.example.com:8080', Validator::authority('www.example.com:8080'));
    $this->assertEquals(
        'user:password@www.example.org:1234',
        Validator::authority('user:password@www.example.org:1234')
    );
});

test('ValidateInvalidAuthority', function () {
    $this->assertNull(Validator::authority('user:password@:1234'));
    $this->assertNull(Validator::authority(''));
});

test('ValidateAuthorityComponents', function () {
    assertArrayContains(
        Validator::authorityComponents('user:password@www.example.org:1234'),
        [
            'userInfo' => 'user:password',
            'user' => 'user',
            'password' => 'password',
            'host' => 'www.example.org',
            'port' => 1234,
        ]
    );
});

test('ValidateInvalidAuthorityComponents', function () {
    $this->assertNull(Validator::authorityComponents('user:password@:1234'));
    $this->assertNull(Validator::authorityComponents(''));
});

test('ValidateUserInfo', function () {
    $this->assertEquals('user:password', Validator::userInfo('user:password'));
    $this->assertEquals('u%C2%A7er:p%C3%A1ssword', Validator::userInfo('u§er:pássword'));
    $this->assertNull(Validator::userInfoComponents(':password'));
});

test('ValidateUserInfoComponents', function () {
    assertArrayContains(
        Validator::userInfoComponents('crwlr:software'),
        [
            'user' => 'crwlr',
            'password' => 'software',
        ]
    );

    assertArrayContains(
        Validator::userInfoComponents('u§er:pássword'),
        [
            'user' => 'u%C2%A7er',
            'password' => 'p%C3%A1ssword',
        ]
    );

    $this->assertNull(Validator::userInfoComponents(':password'));
});

test('ValidateUser', function () {
    $this->assertEquals('user', Validator::user('user'));
    $this->assertEquals('user-123', Validator::user('user-123'));
    $this->assertEquals('user_123', Validator::user('user_123'));
    $this->assertEquals('user%123', Validator::user('user%123'));
    $this->assertEquals('u$3r_n4m3!', Validator::user('u$3r_n4m3!'));
    $this->assertEquals('u$3r\'$_n4m3', Validator::user('u$3r\'$_n4m3'));
    $this->assertEquals('u$3r*n4m3', Validator::user('u$3r*n4m3'));
    $this->assertEquals('u$3r,n4m3', Validator::user('u$3r,n4m3'));
    $this->assertEquals('=u$3r=', Validator::user('=u$3r='));

    $this->assertEquals('u%C2%A73rname', Validator::user('u§3rname'));
    $this->assertEquals('user%3Aname', Validator::user('user:name'));
    $this->assertEquals('%C3%9Csern%C3%A4me', Validator::user('Üsernäme'));
    $this->assertEquals('user%C2%B0name', Validator::user('user°name'));
    $this->assertEquals('%3Cusername%3E', Validator::user('<username>'));
    $this->assertEquals('usern%40me', Validator::user('usern@me'));
    $this->assertEquals('us%E2%82%ACrname', Validator::user('us€rname'));
});

test('ValidatePassword', function () {
    $this->assertEquals('pASS123', Validator::password('pASS123'));
    $this->assertEquals('P4ss.123', Validator::pass('P4ss.123'));
    $this->assertEquals('p4ss~123', Validator::password('p4ss~123'));
    $this->assertEquals('p4ss-123!', Validator::pass('p4ss-123!'));
    $this->assertEquals('p4$$&w0rD', Validator::password('p4$$&w0rD'));
    $this->assertEquals('(p4$$-w0rD)', Validator::pass('(p4$$-w0rD)'));
    $this->assertEquals('p4$$+W0rD', Validator::password('p4$$+W0rD'));
    $this->assertEquals('P4ss;w0rd', Validator::pass('P4ss;w0rd'));

    $this->assertEquals('%22password%22', Validator::password('"password"'));
    $this->assertEquals('pass%60word', Validator::pass('pass`word'));
    $this->assertEquals('pass%5Eword', Validator::password('pass^word'));
    $this->assertEquals('pass%F0%9F%A4%93moji', Validator::pass('pass🤓moji'));
    $this->assertEquals('pass%5Cword', Validator::password('pass\word'));
    $this->assertEquals('pa%C3%9Fword', Validator::pass('paßword'));
});

test('ValidateHost', function () {
    $this->assertEquals('example.com', Validator::host('example.com'));
    $this->assertEquals('www.example.com', Validator::host('www.example.com'));
    $this->assertEquals('www.example.com.', Validator::host('www.example.com.'));
    $this->assertEquals('subdomain.example.com', Validator::host('subdomain.example.com'));
    $this->assertEquals('www.some-domain.io', Validator::host('www.some-domain.io'));
    $this->assertEquals('123456.co.uk', Validator::host('123456.co.uk'));
    $this->assertEquals('www.example.com', Validator::host('WWW.EXAMPLE.COM'));
    $this->assertEquals('www-something.blog', Validator::host('www-something.blog'));
    $this->assertEquals('h4ck0r.software', Validator::host('h4ck0r.software'));
    $this->assertEquals('g33ks.org', Validator::host('g33ks.org'));
    $this->assertEquals('example.xn--80asehdb', Validator::host('example.онлайн'));
    $this->assertEquals('example.xn--80asehdb', Validator::host('example.xn--80asehdb'));
    $this->assertEquals('www.xn--80a7a.com', Validator::host('www.са.com')); // Fake "a" in ca.com => idn domain
    $this->assertEquals('12.34.56.78', Validator::host('12.34.56.78'));
    $this->assertEquals('localhost', Validator::host('localhost'));
    $this->assertEquals('dev.local', Validator::host('dev.local'));

    $this->assertNull(Validator::host('slash/example.com'));
    $this->assertNull(Validator::host('exclamation!mark.co'));
    $this->assertNull(Validator::host('question?mark.blog'));
    $this->assertNull(Validator::host('under_score.org'));
    $this->assertNull(Validator::host('www.(parenthesis).net'));
    $this->assertNull(Validator::host('idk.amper&sand.uk'));
    $this->assertNull(Validator::host('equals=.ch'));
    $this->assertNull(Validator::host('apostrophe\'.at'));
    $this->assertNull(Validator::host('one+one.mobile'));
    $this->assertNull(Validator::host('hash#tag.social'));
    $this->assertNull(Validator::host('co:lon.com'));
    $this->assertNull(Validator::host('semi;colon.net'));
    $this->assertNull(Validator::host('<html>.codes'));
    $this->assertNull(Validator::host('www..com'));
});

test('ValidateDomainSuffix', function () {
    $this->assertEquals('com', Validator::domainSuffix('com'));
    $this->assertEquals('org', Validator::domainSuffix('org'));
    $this->assertEquals('net', Validator::domainSuffix('net'));
    $this->assertEquals('blog', Validator::domainSuffix('blog'));
    $this->assertEquals('codes', Validator::domainSuffix('codes'));
    $this->assertEquals('wtf', Validator::domainSuffix('wtf'));
    $this->assertEquals('sexy', Validator::domainSuffix('sexy'));
    $this->assertEquals('tennis', Validator::domainSuffix('tennis'));
    $this->assertEquals('versicherung', Validator::domainSuffix('versicherung'));
    $this->assertEquals('xn--3pxu8k', Validator::domainSuffix('点看'));
    $this->assertEquals('xn--80asehdb', Validator::domainSuffix('онлайн'));
    $this->assertEquals('xn--pssy2u', Validator::domainSuffix('大拿'));
    $this->assertEquals('co.uk', Validator::domainSuffix('co.uk'));
    $this->assertEquals('co.at', Validator::domainSuffix('co.at'));
    $this->assertEquals('or.at', Validator::domainSuffix('or.at'));
    $this->assertEquals('anything.bd', Validator::domainSuffix('anything.bd'));

    $this->assertNull(Validator::domainSuffix('süffix'));
    $this->assertNull(Validator::domainSuffix('idk'));
});

test('ValidateDomain', function () {
    $this->assertEquals('google.com', Validator::domain('google.com'));
    $this->assertEquals('example.xn--80asehdb', Validator::domain('example.xn--80asehdb'));
    $this->assertEquals('example.xn--80asehdb', Validator::domain('example.онлайн'));

    $this->assertNull(Validator::domain('www.google.com'));
    $this->assertNull(Validator::domain('yolo'));
    $this->assertNull(Validator::domain('subdomain.example.онлайн'));
});

test('ValidateDomainLabel', function () {
    $this->assertEquals('yolo', Validator::domainLabel('yolo'));
    $this->assertEquals('xn--mnnersalon-q5a', Validator::domainLabel('männersalon'));
});

test('ValidateInvalidDomainLabel', function () {
    $this->assertNull(Validator::domainLabel('yo!lo'));
    $this->assertNull(Validator::domainLabel(''));
});

test('ValidateSubdomain', function () {
    $this->assertEquals('www', Validator::subdomain('www'));
    $this->assertEquals('sub.domain', Validator::subdomain('sub.domain'));
    $this->assertEquals('sub.do.main', Validator::subdomain('SUB.DO.MAIN'));

    $this->assertNull(Validator::subdomain('sub_domain'));
});

test('ValidatePort', function () {
    $this->assertEquals(0, Validator::port(0));
    $this->assertEquals(8080, Validator::port(8080));
    $this->assertEquals(65535, Validator::port(65535));

    $this->assertNull(Validator::port(-1));
    $this->assertNull(Validator::port(65536));
});

test('ValidatePath', function () {
    $this->assertEquals('/FoO/bAr', Validator::path('/FoO/bAr'));
    $this->assertEquals('/foo-123/bar_456', Validator::path('/foo-123/bar_456'));
    $this->assertEquals('/~foo/!bar$/&baz\'', Validator::path('/~foo/!bar$/&baz\''));
    $this->assertEquals('/(foo)/*bar+', Validator::path('/(foo)/*bar+'));
    $this->assertEquals('/foo,bar;baz:', Validator::path('/foo,bar;baz:'));
    $this->assertEquals('/foo=bar@baz', Validator::path('/foo=bar@baz'));
    $this->assertEquals('/%22foo%22', Validator::path('/"foo"'));
    $this->assertEquals('/foo%5Cbar', Validator::path('/foo\\bar'));
    $this->assertEquals('/b%C3%B6%C3%9Fer/pfad', Validator::path('/bößer/pfad'));
    $this->assertEquals('/%3Chtml%3E', Validator::path('/<html>'));

    // Percent character not encoded (to %25) because %ba could be legitimate percent encoded character.
    $this->assertEquals('/foo%bar', Validator::path('/foo%bar'));

    // Percent character encoded because %ga isn't a valid percent encoded character.
    $this->assertEquals('/foo%25gar', Validator::path('/foo%gar'));
});

test('ValidateQuery', function () {
    $this->assertEquals('foo=bar', Validator::query('foo=bar'));
    $this->assertEquals('foo=bar', Validator::query('?foo=bar'));
    $this->assertEquals('foo1=bar&foo2=baz', Validator::query('foo1=bar&foo2=baz'));
    $this->assertEquals('.foo-=_bar~', Validator::query('.foo-=_bar~'));
    $this->assertEquals('%25foo!=$bar\'', Validator::query('%foo!=$bar\''));
    $this->assertEquals('(foo)=*bar+', Validator::query('(foo)=*bar+'));
    $this->assertEquals('f,o;o==bar:', Validator::query('f,o;o==bar:'));
    $this->assertEquals('@foo=/bar%3F', Validator::query('?@foo=/bar?'));
    $this->assertEquals('%22foo%22=bar', Validator::query('"foo"=bar'));
    $this->assertEquals('foo%23=bar', Validator::query('foo#=bar'));
    $this->assertEquals('f%C3%B6o=bar', Validator::query('föo=bar'));
    $this->assertEquals('boe%C3%9Fer=query', Validator::query('boeßer=query'));
    $this->assertEquals('foo%60=bar', Validator::query('foo`=bar'));
    $this->assertEquals('foo%25bar=baz', Validator::query('foo%25bar=baz'));
});

test('ValidateFragment', function () {
    $this->assertEquals('fragment', Validator::fragment('fragment'));
    $this->assertEquals('fragment', Validator::fragment('#fragment'));
    $this->assertEquals('fragment1234567890', Validator::fragment('fragment1234567890'));
    $this->assertEquals('-.fragment_~', Validator::fragment('-.fragment_~'));
    $this->assertEquals('%25!fragment$&', Validator::fragment('%!fragment$&'));
    $this->assertEquals('(\'fragment*)', Validator::fragment('(\'fragment*)'));
    $this->assertEquals('+,fragment;:', Validator::fragment('#+,fragment;:'));
    $this->assertEquals('@=fragment/?', Validator::fragment('@=fragment/?'));
    $this->assertEquals('%22fragment%22', Validator::fragment('#"fragment"'));
    $this->assertEquals('fragment%23', Validator::fragment('#fragment#'));
    $this->assertEquals('%23fragment', Validator::fragment('##fragment'));
    $this->assertEquals('fr%C3%A4gment', Validator::fragment('frägment'));
    $this->assertEquals('boe%C3%9Fesfragment', Validator::fragment('boeßesfragment'));
    $this->assertEquals('fragment%60', Validator::fragment('fragment`'));
    $this->assertEquals('fragm%E2%82%ACnt', Validator::fragment('fragm%E2%82%ACnt'));
});

/**
 * @param mixed $validationResult
 * @param mixed[] $contains
 */
function assertArrayContains(mixed $validationResult, array $contains): void
{
    Assert::assertIsArray($validationResult);

    foreach ($contains as $key => $value) {
        Assert::assertArrayHasKey($key, $validationResult);
        Assert::assertEquals($value, $validationResult[$key]);
    }
}
