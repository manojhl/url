<?php

namespace Crwlr\Url;

/**
 * Class Validator
 *
 * This class has all the validation logic. It validates a full URL or single URL components.
 */

class Validator
{
    /**
     * Validate a URL
     *
     * Returns a valid URL as string or null for invalid URLs.
     */
    public static function url(string $url): ?string
    {
        if (trim($url) === '') {
            return '';
        }

        $validComponents = self::getValidUrlComponents($url);

        if ($validComponents) {
            return Helpers::buildUrlFromComponents($validComponents);
        }

        return null;
    }

    /**
     * Get valid URL and all contained components as array
     *
     * Returns an array like ['url' => '...', 'scheme' => '...'] or null for invalid URL.
     *
     * @return null|array<string|int>
     */
    public static function urlAndComponents(string $url): ?array
    {
        if (trim($url) === '') {
            return ['url' => '', 'path' => ''];
        }

        return self::returnValidUrlAndComponentsArray(self::getValidUrlComponents($url));
    }

    /**
     * Validate an absolute URL
     *
     * Same as method url() but only an absolute URL is valid. Returns null for relative references.
     */
    public static function absoluteUrl(string $url): ?string
    {
        if (trim($url) === '') {
            return '';
        }

        $validComponents = self::getValidUrlComponents($url, true);

        if ($validComponents) {
            return Helpers::buildUrlFromComponents($validComponents);
        }

        return null;
    }

    /**
     * Get valid URL and all contained components as array
     *
     * Same as method urlAndComponents() but only an absolute URL is valid. Returns null for relative references.
     *
     * @return null|array<string|int>
     */
    public static function absoluteUrlAndComponents(string $url): ?array
    {
        if (trim($url) === '') {
            return null;
        }

        return self::returnValidUrlAndComponentsArray(self::getValidUrlComponents($url, true));
    }

    /**
     * Validate a scheme
     *
     * Returns the valid lowercase scheme or null when input scheme is invalid.
     */
    public static function scheme(string $scheme): ?string
    {
        $scheme = strtolower(trim($scheme));

        if (Helpers::schemes()->exists($scheme)) {
            return $scheme;
        }

        return null;
    }

    /**
     * Validate an authority
     *
     * Percent-encodes user information (user, password) and encodes internationalized domain names.
     * Returns null if any component is invalid.
     */
    public static function authority(string $authority): ?string
    {
        $components = self::getValidAuthorityComponents($authority);

        if ($components) {
            return ($components['userInfo'] ? $components['userInfo'] . '@' : '') . $components['host'] .
                ($components['port'] ? ':' . $components['port'] : '');
        }

        return null;
    }

    /**
     * Get valid components of an authority
     *
     * Components are: host, userInfo, user, password, port.
     * So you get the userInfo as one string (<user>:<password>) and also the user and password separately.
     * Returns null if any component is invalid.
     *
     * @return null|array<string|int>
     */
    public static function authorityComponents(string $authority): ?array
    {
        $components = self::getValidAuthorityComponents($authority);

        if ($components) {
            return $components;
        }

        return null;
    }

    /**
     * Validate user information
     *
     * Percent-encodes special characters. Returns null for invalid user information.
     */
    public static function userInfo(string $userInfo): ?string
    {
        $components = self::getValidUserInfoComponents($userInfo);

        if ($components) {
            return ($components['user'] ?? '') . ($components['password'] ? ':' . $components['password'] : '');
        }

        return null;
    }

    /**
     * Get valid user information components (user, password)
     *
     * Percent-encodes special characters. Returns null for invalid user information.
     *
     * @return string[]|null
     */
    public static function userInfoComponents(string $userInfo): ?array
    {
        $components = self::getValidUserInfoComponents($userInfo);

        if ($components) {
            return $components;
        }

        return null;
    }

    /**
     * Validate (only) the user from the user info
     */
    public static function user(string $user): string
    {
        return self::userOrPassword($user);
    }

    /**
     * Validate (only) the password from the user info
     */
    public static function password(string $password): string
    {
        return self::userOrPassword($password);
    }

    /**
     * Alias for method password
     */
    public static function pass(string $pass): string
    {
        return self::password($pass);
    }

    /**
     * Validate a host
     *
     * Returns the valid host string or null for invalid host.
     * Internationalized domain names will be encoded.
     */
    public static function host(string $host): ?string
    {
        if (trim($host) !== '') {
            if (self::isIpHost($host)) {
                return strtolower($host);
            }

            $host = self::encodeIdnAndLowercase($host);

            if (self::containsOnly($host, self::hostCharacters()) && self::hostLabelsAreValid($host)) {
                return $host;
            }
        }

        return null;
    }

    /**
     * Validate a registrable domain
     *
     * Returns a valid registrable domain or null if invalid.
     * Returns null when a subdomain is included, so don't use this method to validate a host.
     */
    public static function domain(string $domain): ?string
    {
        if ($domain !== '') {
            $domain = self::encodeIdnAndLowercase($domain);

            if (self::containsOnly($domain, self::hostCharacters())) {
                $suffix = Helpers::suffixes()->getByHost($domain);

                if ($suffix) {
                    // The registrable domain part of the host doesn't contain a subdomain, so if $domain
                    // without the public suffix contains a ".", it's not a valid registrable domain.
                    $domainWithoutSuffix = Helpers::stripFromEnd($domain, '.' . $suffix);

                    if ($domainWithoutSuffix !== '' && !str_contains($domainWithoutSuffix, '.')) {
                        return $domain;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate the label of a registrable domain (domain without suffix)
     */
    public static function domainLabel(string $domainLabel): ?string
    {
        if ($domainLabel !== '') {
            $domainLabel = self::encodeIdnAndLowercase($domainLabel);

            if (self::containsOnly($domainLabel, self::hostCharacters(true))) {
                return $domainLabel;
            }
        }

        return null;
    }

    /**
     * Validate a public domain suffix
     *
     * Returns the valid domain suffix or null if invalid.
     * Suffixes of internationalized domain names will be encoded.
     */
    public static function domainSuffix(string $domainSuffix): ?string
    {
        if (trim($domainSuffix) !== '') {
            $domainSuffix = self::encodeIdnAndLowercase($domainSuffix);

            if (
                self::containsOnly($domainSuffix, self::hostCharacters()) &&
                Helpers::suffixes()->exists($domainSuffix)
            ) {
                return $domainSuffix;
            }
        }

        return null;
    }

    /**
     * Validate a subdomain
     *
     * Returns the valid subdomain or null if invalid. Disallowed characters will be encoded.
     */
    public static function subdomain(string $subdomain): ?string
    {
        if (trim($subdomain) !== '') {
            $subdomain = self::encodeIdnAndLowercase($subdomain);

            if (self::containsOnly($subdomain, self::hostCharacters())) {
                return $subdomain;
            }
        }

        return null;
    }

    /**
     * Validate a port
     *
     * Returns the valid port as int or null when port is not in allowed range (0 to 65535).
     */
    public static function port(int $port): ?int
    {
        return $port >= 0 && $port <= 65535 ? $port : null;
    }

    /**
     * Validate path component
     *
     * Returns path string percent-encoded according to https://tools.ietf.org/html/rfc3986#section-3.3
     * or null for an invalid path.
     *
     * When the URL doesn't contain an authority component, it can't start with more than one slash.
     * If it doesn't start with a slash (relative-path reference) it must not contain a colon in the first segment.
     *
     * @param bool $hasAuthority  Set to false when the URI containing that path has no authority component.
     */
    public static function path(string $path, bool $hasAuthority = true): ?string
    {
        if (
            $hasAuthority === false &&
            (
                Helpers::startsWith($path, '//', 2) ||
                (!Helpers::startsWith($path, '/', 1) && Helpers::containsXBeforeFirstY($path, ':', '/'))
            )
        ) {
            return null;
        }

        $path = self::encodePercentCharacter($path);

        return self::urlEncodeExcept($path, self::pcharRegexPattern(['/', '%']));
    }

    /**
     * Validate query string
     *
     * Returns query string percent-encoded according to https://tools.ietf.org/html/rfc3986#section-3.4
     */
    public static function query(string $query = ''): string
    {
        if (str_starts_with($query, '?')) {
            $query = substr($query, 1);
        }

        $query = self::encodePercentCharacter($query);

        return self::urlEncodeExcept($query, self::pcharRegexPattern(['/', '%']));
    }

    /**
     * Validate fragment component
     *
     * Returns fragment percent-encoded according to https://tools.ietf.org/html/rfc3986#section-3.5
     */
    public static function fragment(string $fragment = ''): string
    {
        if (str_starts_with($fragment, '#')) {
            $fragment = substr($fragment, 1);
        }

        $fragment = self::encodePercentCharacter($fragment);

        return self::urlEncodeExcept($fragment, self::pcharRegexPattern(['/', '?', '%']));
    }

    /**
     * Validate a component value by a variable component name
     *
     * This method is here to avoid calling validation like self::$componentName() and thereby loosing traceability
     * of method calls for IDEs.
     */
    public static function callValidationByComponentName(string $componentName, mixed $value): string|int|null
    {
        if ($componentName === 'scheme') {
            return self::scheme($value);
        } elseif ($componentName === 'authority') {
            return self::authority($value);
        } elseif ($componentName === 'userInfo') {
            return self::userInfo($value);
        } elseif ($componentName === 'user') {
            return self::userOrPassword($value);
        } elseif ($componentName === 'password' || $componentName === 'pass') {
            return self::userOrPassword($value);
        } elseif ($componentName === 'host') {
            return self::host($value);
        } elseif ($componentName === 'domain') {
            return self::domain($value);
        } elseif ($componentName === 'domainLabel') {
            return self::domainLabel($value);
        } elseif ($componentName === 'domainSuffix') {
            return self::domainSuffix($value);
        } elseif ($componentName === 'subdomain') {
            return self::subdomain($value);
        } elseif ($componentName === 'port') {
            return self::port($value);
        } elseif ($componentName === 'path') {
            return self::path($value);
        } elseif ($componentName === 'query') {
            return self::query($value);
        } elseif ($componentName === 'fragment') {
            return self::fragment($value);
        }

        return null;
    }

    /**
     * Get all valid URL components from the provided URL string as array.
     *
     * In case of an invalid URL null is returned.
     *
     * @param bool $onlyAbsoluteUrl  When set to true, it will also return null when the input is a relative reference.
     * @return null|array<string|int>
     */
    private static function getValidUrlComponents(string $url, bool $onlyAbsoluteUrl = false): ?array
    {
        $url = self::encodeIdnHostInUrl($url);
        $components = parse_url($url);

        if (
            is_array($components) &&
            !empty($components) &&
            ($onlyAbsoluteUrl === false || filter_var($url, FILTER_VALIDATE_URL) !== false)
        ) {
            $components = self::filterEmptyStringComponents($components);
            $validComponents = self::validateUrlComponents($components);

            if (!empty($validComponents)) {
                return $validComponents;
            }
        }

        return null;
    }

    /**
     * Encode internationalized domain names in a URL
     *
     * PHPs parse_url method breaks special characters in internationalized domain names. So this method
     * uses the getAuthorityFromUrl method below to find the host part, checks for not allowed characters and handles
     * encoding if needed.
     */
    private static function encodeIdnHostInUrl(string $url): string
    {
        $authority = self::getAuthorityFromUrl($url);

        if ($authority === null || self::containsOnly($authority, self::hostCharacters())) {
            return $url;
        }

        $authority = self::stripUserInfoFromAuthority($authority);
        $host = self::stripPortFromAuthority($authority);

        if ($host !== '' && !self::isIpHost($host) && !self::containsOnly($host, self::hostCharacters())) {
            $encodedHost = Helpers::idn_to_ascii($host);
            $url = Helpers::replaceFirstOccurrence($host, $encodedHost, $url);
        }

        return $url;
    }

    /**
     * Manually find the authority part in a URL
     *
     * PHPs parse_url method breaks special characters in internationalized domain names.
     * This method manually extracts the authority component from a URL (if exists) without breaking special characters.
     *
     * @see self::encodeIdnHostInUrl()
     */
    private static function getAuthorityFromUrl(string $url = ''): ?string
    {
        if (Helpers::startsWith($url, '//', 2)) { // Protocol relative like //www.example.com/path
            $urlWithoutScheme = $url;
        } elseif (Helpers::startsWith($url, '/', 1)) { // It's a relative reference (path).
            return null;
        } else {
            $urlWithoutScheme = self::stripSchemeFromUrl($url);

            if ($url === $urlWithoutScheme) {
                return null;
            }
        }

        foreach (explode('/', $urlWithoutScheme) as $part) {
            if ($part !== '') {
                return $part;
            }
        }

        return null;
    }

    /**
     * Manually strip the scheme part from a URL
     *
     * Helper method for getAuthorityFromUrl method.
     *
     * @see self::getAuthorityFromUrl()
     */
    private static function stripSchemeFromUrl(string $url = ''): string
    {
        $splitAtColon = explode(':', $url);

        if (count($splitAtColon) === 1) {
            return $url;
        }

        unset($splitAtColon[0]);

        return implode(':', $splitAtColon);
    }

    /**
     * Strip the user information at the beginning of an authority (if it contains user information)
     */
    private static function stripUserInfoFromAuthority(string $authority, ?string $userInfo = null): string
    {
        if (!$userInfo) {
            $userInfo = self::getUserInfoFromAuthority($authority);
        }

        if ($userInfo === '') {
            return $authority;
        }

        return Helpers::stripFromStart($authority, $userInfo . '@');
    }

    /**
     * Get the user info part from an authority.
     */
    private static function getUserInfoFromAuthority(string $authority): string
    {
        if (str_contains($authority, '@')) {
            $splitAtAt = explode('@', $authority);

            if (count($splitAtAt) > 1) {
                return Helpers::stripFromEnd($authority, '@' . end($splitAtAt));
            }
        }

        return '';
    }

    /**
     * Strip the port at the end of an authority if there is one.
     *
     * @param null|int $port  When the port is already known it doesn't have to be extracted again.
     */
    private static function stripPortFromAuthority(string $authority, ?int $port = null): string
    {
        if (!$port) {
            $port = self::getPortFromAuthority($authority);
        }

        if ($port) {
            return Helpers::stripFromEnd($authority, ':' . $port);
        }

        return $authority;
    }

    /**
     * Get the port from an authority string
     *
     * Returns null if the authority does not include a port.
     */
    private static function getPortFromAuthority(string $authority): ?int
    {
        $splitAtColon = explode(':', $authority);

        if (count($splitAtColon) > 1) {
            $potentialPort = end($splitAtColon);

            if (is_numeric($potentialPort)) {
                return (int) $potentialPort;
            }
        }

        return null;
    }

    /**
     * Filter empty string elements from array returned by parse_url()
     *
     * In PHP 7 parsing URLs containing a delimiter for a component followed by nothing or another delimiter
     * (e.g. https://example.com/foo?#) returns an array without the keys (query and fragment). In PHP 8 that changed
     * and the returned array contains query and fragment with empty strings as values.
     * Remove empty string elements for the same outcome in both versions.
     *
     * @param array<string|int> $components
     * @return array<string|int>
     */
    private static function filterEmptyStringComponents(array $components = []): array
    {
        foreach ($components as $componentName => $componentValue) {
            if ($componentValue === '') {
                unset($components[$componentName]);
            }
        }

        return $components;
    }

    /**
     * Validate an array of URL components.
     *
     * Returns an empty array when one of the components is invalid.
     *
     * @param array<string|int> $components
     * @return array<string|int>
     */
    private static function validateUrlComponents(array $components): array
    {
        foreach ($components as $componentName => $componentValue) {
            if (method_exists(self::class, $componentName)) {
                if ($componentName === 'path') {
                    $validComponent = self::path($componentValue, isset($components['host']));
                } else {
                    $validComponent = self::callValidationByComponentName($componentName, $componentValue);
                }

                if ($validComponent === null) {
                    return [];
                }

                $components[$componentName] = $validComponent;
            }
        }

        return $components;
    }

    /**
     * Helper method for the url and absoluteUrl methods.
     *
     * Because it's the same for both methods.
     *
     * @param null|array<string|int> $validComponents
     * @return null|array<string|int>
     */
    private static function returnValidUrlAndComponentsArray(?array $validComponents): ?array
    {
        if (!$validComponents) {
            return null;
        }

        $validComponents['url'] = Helpers::buildUrlFromComponents($validComponents);

        return $validComponents;
    }

    /**
     * Get an array of valid authority components (host, userInfo, user, password, port) from an authority string
     *
     * @return null|array<string|int>
     */
    private static function getValidAuthorityComponents(string $authority): ?array
    {
        $components = self::splitAuthorityToComponents($authority);

        if (!$components) {
            return null;
        }

        $validComponents = self::validateAuthorityComponents($components);

        if ($validComponents) {
            return $validComponents;
        }

        return null;
    }

    /**
     * Split an authority string to components (host, userInfo, port)
     *
     * @return null|array<string|int>
     */
    private static function splitAuthorityToComponents(string $authority): ?array
    {
        $userInfo = self::getUserInfoFromAuthority($authority);
        $userInfoArray = ['user' => null, 'password' => null];

        if ($userInfo) {
            $authority = self::stripUserInfoFromAuthority($authority, $userInfo);
            $userInfoArray = self::splitUserInfoToComponents($userInfo);
        }

        $port = self::getPortFromAuthority($authority);

        if ($port) {
            $authority = self::stripPortFromAuthority($authority, $port);
        }

        if (!empty($authority)) {
            return [
                'userInfo' => $userInfo,
                'user' => $userInfoArray['user'],
                'password' => $userInfoArray['password'],
                'host' => $authority,
                'port' => $port
            ];
        }

        return null;
    }

    /**
     * Split user info string <user>:<password> to user and password
     *
     * @return string[]|null
     */
    private static function splitUserInfoToComponents(string $userInfo): ?array
    {
        $splitAtColon = explode(':', $userInfo);
        $user = $splitAtColon[0];

        if ($user === '') {
            return null;
        }

        $password = null;

        if (count($splitAtColon) > 1) {
            unset($splitAtColon[0]);
            $password = implode(':', $splitAtColon);
        }

        return ['user' => $user, 'password' => $password];
    }

    /**
     * Validate authority components (host, userInfo, port)
     *
     * @param array<string|int> $components
     * @return null|array<string|int>
     */
    private static function validateAuthorityComponents(array $components): ?array
    {
        if (!$components['host']) {
            return null;
        }

        foreach ($components as $componentName => $value) {
            if ($value) {
                $components[$componentName] = self::callValidationByComponentName($componentName, $value);

                if (!$components[$componentName]) {
                    return null;
                }
            }
        }

        return $components;
    }

    /**
     * Split user info string to user and password and validate.
     *
     * @return string[]|null
     */
    private static function getValidUserInfoComponents(string $userInfo): ?array
    {
        $components = self::splitUserInfoToComponents($userInfo);

        if (!$components) {
            return null;
        }

        $components['user'] = self::userOrPassword($components['user']);

        if ($components['password']) {
            $components['password'] = self::userOrPassword($components['password']);
        }

        return $components;
    }

    /**
     * Validate a username or password
     *
     * Percent encodes characters that aren't allowed within a user information component.
     *
     * As this method only validates either a user or a password, the : is not allowed, because it's used to separate
     * user and password.
     */
    private static function userOrPassword(string $string): string
    {
        $string = self::encodePercentCharacter($string);

        return self::urlEncodeExcept($string, "/[^a-zA-Z0-9-._~!$&'()*+,;=%]/");
    }

    /**
     * Returns true when host is an IP address
     */
    private static function isIpHost(string $host): bool
    {
        if (Helpers::startsWith($host, '[', 1) && str_ends_with($host, ']')) {
            $host = substr($host, 1, strlen($host) - 2);
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        return false;
    }

    /**
     * Encode a (potential) internationalized domain name and convert to lowercase
     */
    private static function encodeIdnAndLowercase(string $string): string
    {
        return strtolower(self::encodeIdn($string));
    }

    /**
     * Encode a (potential) internationalized domain name
     *
     * Also decode percent encoded characters first.
     */
    private static function encodeIdn(string $string): string
    {
        if (preg_match('/%[0-9A-Fa-f][0-9A-Fa-f]/', $string)) {
            $string = rawurldecode($string);
        }

        if (!self::containsOnly($string, self::hostCharacters())) {
            $string = Helpers::idn_to_ascii($string);
        }

        return $string;
    }

    /**
     * Returns true when subject string contains only characters matching the characters in regex pattern.
     *
     * @param string $regexPattern  Only characters, no square brackets for character class or delimiters.
     */
    private static function containsOnly(string $subject, string $regexPattern): bool
    {
        return !preg_match('/[^' . $regexPattern . ']/', $subject);
    }

    /**
     * Returns regex pattern to match characters valid in host name
     *
     * Without delimiters and square brackets for character class.
     *
     * @param bool $noDot  Set to true in case you want to match only labels not including "."
     */
    private static function hostCharacters(bool $noDot = false): string
    {
        return 'a-zA-Z0-9\-' . ($noDot ? '' : '\.');
    }

    /**
     * Further validation checks of host labels
     *
     * Checks for empty labels within a host name and if each labels starts and ends with alphanumeric character.
     * https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private static function hostLabelsAreValid(string $host): bool
    {
        $splitLabels = explode('.', $host);

        foreach ($splitLabels as $key => $label) {
            if ($label === '') {
                if ($key === count($splitLabels) - 1) { // Last element in array empty means trailing dot
                    return true;
                }

                return false; // Empty label is invalid
            }

            if (!preg_match('/^[a-zA-Z0-9]/', $label) || !preg_match('/[a-zA-Z0-9]$/', $label)) {
                return false; // Label must start and end with alphanumeric character.
            }
        }

        return true;
    }

    /**
     * Encode percent character in path, query or fragment
     *
     * If the string (path, query, fragment) contains a percent character that is not part of an already percent
     * encoded character it must be encoded (% => %25). So this method replaces all percent characters that are not
     * followed by a hex code.
     */
    private static function encodePercentCharacter(string $string = ''): string
    {
        return preg_replace('/%(?![0-9A-Fa-f][0-9A-Fa-f])/', '%25', $string) ?: $string;
    }

    /**
     * URL encode all characters except those from a certain regex pattern
     */
    private static function urlEncodeExcept(string $encode, string $exceptRegexPattern): string
    {
        return preg_replace_callback(
            $exceptRegexPattern,
            function ($match) {
                return rawurlencode($match[0]);
            },
            $encode
        );
    }

    /**
     * Return the regex pattern for pchar (optionally plus additional characters)
     *
     * https://tools.ietf.org/html/rfc3986#appendix-A
     *
     * @param string[] $additionalCharacters
     */
    private static function pcharRegexPattern(array $additionalCharacters = []): string
    {
        $pattern = "/[^a-zA-Z0-9-._~!$&\'()*+,;=:@";

        foreach ($additionalCharacters as $character) {
            $pattern .= preg_quote($character, '/');
        }

        return $pattern . "]/";
    }
}
