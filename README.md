<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Signika:wght@300..700&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
<br/>
<div align="center">
<a href="https://github.com/GamerBah/govee-control">
<img src="https://gamerbah.com/govee/Banner.png" alt="Logo" width="auto" height="100">
</a>
<h2 align="center">Controllify for Govee</h2>
<p align="center">
A web-app for using Govee lights the smarter way
<br/>
<br/>
<a href="https://gamerbah.com/govee/demo">View Demo</a> |
<a href="https://github.com/GamerBah/govee-control/issues/new?labels=bug">Report a Bug</a> |
<a href="https://github.com/GamerBah/govee-control/issues/new?labels=enhancement">Feature Request</a>
</p>
</div>

# About Controllify

---

Govee lights are known for being competitively-priced smart lights with loads of features.
However, their official mobile app leaves a lot to be desired, and generally feels a bit clunky to use.
They have been working on a desktop application for Windows, however that application has the same shortcomings -- if
not more -- than the mobile app.
<br>
<br>
This is where Controllify aims to thrive.
<br>
<br>
Thanks to Govee making their public API much more usable recently,
Controllify seeks to fill the feature void left by the official desktop and mobile apps, all while being accessible from
anywhere you could use the official app.
<br>

# What Controllify Is

---

Controllify is meant to fill the feature void in three areas:

- Device Management
- Preset Management
- External Control

### 💡 Device Management

Managing a smart light in the official Govee app is... slow, to say the least. But it's no fault of the app itself, it
simply must function that way.
A full refresh of the data from that light is required BEFORE any changes can be made. Makes sense.
<br>
<br>
Controllify, on the other hand, operates on a "do first, ask questions later" approach.
Any data can still be refreshed automatically if Controllify feels it's too old to be reliable, but, by default, trusts
the user to refresh the data when they make changes.
If the user makes a request that isn't accepted by Govee because of outdated device info, everything will be refreshed
to stay up to date. This way, if you don't make any changes to your devices,
you can experience faster response times when it comes to turning a light on or off, or activating a DIY preset.

### 💡 Preset Management

Preset creation in the official app is great, but using them could be much more user-friendly, especially when it comes
to grouping different presets.
Controllify allows you to create your own group presets: Individual lights with individual actions/states, all
controlled with a single toggle.
Have 6 different DIYs for 6 different lights that you want playing at the same time? No problem! Create a preset in
Controllify with a few clicks, and active the custom preset in seconds.
No long loading times or data refreshes.

### 💡External Control

Probably the most important feature of Controllify is the ability to use all of these features through external web
requests.
Things such as StreamDecks, the Lumia Software, Aitum software, etc. can all harness the power of HTTP web requests to
call external APIs.
However, not everyone has the knowledge or patience to create such requests.
<br>
<br>
Controllify handles all the hard work for you. Have a single DIY preset or color temperature change to make on a light?
Copy the web request body and use it in applications by making a call to Controllify's own API handler.
Even better, for custom presets, all actions can be sent as a single request to Controllify's server, and Controllify
will handle the rest and split each request up for Govee to accept.
No longer will you have to create a long list of web requests in some external software just to get your lights looking
the way you want them. Send a single request to Controllify to simplify the process. Do more, faster.

# What Controllify Isn't

---

Controllify is not a replacement for all the functionality of the official Govee apps. Creating device DIY lighting
presets in the official app works well enough to not need a replacement.
With the constant addition of new devices to the Govee ecosystem, it is far better to leave the creation of lighting
presets to them.
<br>

# Requirements

---
You will need a Govee API key in order to use Controllify. If you don't have one, you can apply for a key inside the
official Govee Home app on Android or iOS.
[Click here for instructions/more info.](https://govee.readme.io/reference/applying-for-an-api-key)

*A note from Govee's site:*
> Govee only supports one API Key per user tied to your Gove Home account.
> The API Keys are sent to your registered email. Keep this API Key safe.
> If you suspect your API Key is compromised, apply for a new API Key, and a new API Key will be emailed to you,
> and your previous key will be deleted.

# Roadmap

---

As Controllify is still in its early stages, my roadmap for potential features is slim. However, as things are requested
and approved to be added, they will be added here.

- [ ] Improve mobile user experience
- [ ] Allow for device power, color temp, and brightness states in custom presets
- [ ] Improve new user experience
- [ ] Color Palette tweaks
- [ ] Auto refresh of potentially stale data
- [ ] Proper error feedback to users
- [ ] Display internal API call limit from Govee to users
- [ ] User Accounts
- [ ] Secondary Users / API sub-keys (let allowed users control your lights)

# Contact

---

If you have a feature suggestion or need some other means of contacting me, feel free to reach out through

- The [Controllify Issues page](https://github.com/GamerBah/govee-control/issues)
- X: [@GamerBah_](https://x.com/GamerBah_)
- My business email: [business@gamerbah.com](mailto:business@gamerbah.com)
