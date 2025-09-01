using System;
using System.Collections.Generic;
using System.Data;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Security.Claims;
using System.Threading.Tasks;
using System.Web;
 
using System.Web.Http;
using System.Web.Security;
using System.Web.Services.Description;
using Antlr.Runtime.Misc;
using EHub.Helpers;
using EHub.Models;

namespace EHub.Controllers
{
    public class ArticleController : ApiController
    {

        [HttpGet]
        [Route("api/article/{id}")]
        public IHttpActionResult getArticle(int id)
        {
            var EmployeeId = ((ClaimsIdentity)User.Identity).Claims.FirstOrDefault(u => u.Type.Equals("EmployeeId")).Value;


            using (var db = new EHubEntities())
            {
                try
                {


                    var s = db.Article.Where(a => a.ArticleID == id).FirstOrDefault();
                    if (s == null)
                    {
                        return NotFound();
                    }

                    var rclist = db.RoleToArticle.Where(rl => rl.ArticleId == id).Select(rl => rl.RoleId.ToString()).ToList();
                    var taglist = Helper.getTagsByContent(id, 1);
                    var functionlist = Helper.getFunctionsByContent(id, 1);
                    var programlist = Helper.getProgramsByContent(id, 1);

                    var sitelist = Helper.getSitesByContent(id, 1);

                    var c = db.Content.Where(cc => cc.RefID == s.ArticleID && cc.ContentTypeID == 1).FirstOrDefault();
                    var ret = new ArticleListDTO
                    {
                        CreatedDate = c.CreatedDate,
                        UpdatedDate = c.UpdatedDate,
                        ArticleID = s.ArticleID,
                        Title = s.Title,
                        Body = s.Body,
                        PinOnTop = s.PinOnTop,
                        PreviewText = s.PreviewText,
                        PreviewImage = s.PreviewImage,
                        PreviewFile = s.PreviewFile,
                        Active = c.Active,
                        ExpirationDate = s.ExpirationDate,
                        AuthorName = s.AuthorName,
                        Author = s.Author,
                        BackgroundColor = s.BackgroundColor,
                        like = db.ArticleLikes.Where(e => e.ArticleId == s.ArticleID && "" + e.EmployeeId == EmployeeId).Count() > 0,
                        likes = db.ArticleLikes.Where(e => e.ArticleId == s.ArticleID).Count(),
                        roles = (rclist.Count > 0) ? String.Join(",", rclist) : "",
                        tags = (taglist.Count > 0) ? String.Join(",", taglist) : "",
                        sites = (sitelist.Count > 0) ? String.Join(",", sitelist) : "",
                        programs = (programlist.Count > 0) ? String.Join(",", programlist) : "",
                        functions = (functionlist.Count > 0) ? String.Join(",", functionlist) : "",

                        files = db.ArticleFile.Where(a => a.Active && a.ArticleId == s.ArticleID).Select(a => new
                        ArticleFileListDTO
                        {
                            id = a.id,
                            FileName = a.FileName,
                            CreatedDate = a.CreatedDate
                        }).ToList()         
                      };



                    return Ok(ret);

                }
                catch (Exception e)
                {
                    return BadRequest(e.Message);
                }

            }
        }


        [HttpPost]
        [Route("api/article/list")]
        public IHttpActionResult getList(ArticleFilterDTO filter, int page = 1, int perPage = 0)
        {
            // var identity = IdentityManager.GetUserIdentity();
            int? SiteId = null;
            int? ProgramId = null;
            int? FunctionId = null;
            string function = "";


            string UserId = "";


         
            ClaimsIdentity identity = (ClaimsIdentity)User.Identity;

            foreach (var cl in identity.Claims)
            {
                if (cl.Type == "EmployeeId")
                {
                    UserId = cl.Value;

                }

            }

            int userid = int.Parse(UserId);

            using (var db = new EmployeeManagerEntities())
            {
          

                var emp = db.Employees.Where(e => e.Id == userid).FirstOrDefault();
                if (emp != null)
                {
                    SiteId = emp.SiteId;
                    ProgramId = emp.ProgramId;
                    function = emp.JobFunction;


                }
            }

            if (!string.IsNullOrEmpty(function))
            {
                using (var db = new OTD_MasterDataEntities())
                {


                    var f = db.Functions.Where(e => e.FunctionName == function).FirstOrDefault();
                    if (f == null)
                    {
                        if (!string.IsNullOrEmpty(filter.roles))
                        {
                            return BadRequest("Unknown function " + function);
                        }

                    } else
                    {
                        FunctionId = f.id;
                    }


                }

            }


            using (var db = new EHubEntities())
            {
                try
                {


                    var list = (from a in db.Article
                                join c in db.Content on a.ArticleID equals c.RefID
                                where c.ContentTypeID == 1
                                orderby a.PinOnTop descending, c.UpdatedDate descending
                                select new ArticleListDTO
                                {
                                    CreatedDate = c.CreatedDate,
                                    UpdatedDate = c.UpdatedDate,
                                    ArticleID = a.ArticleID,
                                    Title = a.Title,
                                    Active = c.Active,
                                    PinOnTop = a.PinOnTop,
                                    Body = a.Body,
                                    PreviewText = a.PreviewText,
                                    PreviewImage = a.PreviewImage,
                                    PreviewFile = a.PreviewFile,
                                    Author = a.Author,
                                    AuthorName = a.AuthorName,
                                    ExpirationDate = a.ExpirationDate,
                                    BackgroundColor = a.BackgroundColor,
                                    like = db.ArticleLikes.Where(e => e.ArticleId == a.ArticleID && "" + e.EmployeeId == UserId).Count() > 0,
                                    likes = db.ArticleLikes.Where(e => e.ArticleId == a.ArticleID).Count(),


                                }
                                ).AsQueryable();



                    //var list = db.viewArticle.OrderByDescending(p => p.PinOnTop).AsQueryable();

                    if (!filter.showall)
                    {
                        list = list.Where(s => s.Active).Where(d => d.ExpirationDate.HasValue && d.ExpirationDate > DateTime.Now);
                    }
                    if (filter.Author > 0)
                    {
                        list = list.Where(s => s.Author == filter.Author);
                    }

                    if (!string.IsNullOrEmpty(filter.search))
                    {
                        list = list.Where(s => s.Title.Contains(filter.search) || s.PreviewText.Contains(filter.search) || s.Body.Contains(filter.search));
                    }


                    if (!string.IsNullOrEmpty(filter.roles))
                    {
                        var rr = filter.roles.Split(new char[] { ',' });

                        var alist = db.RoleToArticle.Where(rl => rr.Contains("" + rl.RoleId)).Select(rl => rl.ArticleId).ToList();
                        var assigned = db.RoleToArticle.Where(c => c.Active).Select(c => c.ArticleId).ToList();
                        foreach (var id in db.Article.Where(c => assigned.Contains(c.ArticleID) == false).Select(c => c.ArticleID).ToList())
                        {
                            alist.Add(id);
                        }
                        list = list.Where(s => alist.Contains(s.ArticleID) ||  s.Author == userid);


                        if (SiteId.HasValue)
                        {
                            var articles = Helper.getContentBySites("" + SiteId, 1, true);
                            list = list.Where(s => articles.Contains(s.ArticleID) ||   s.Author == userid);
                        }
                        if (ProgramId.HasValue)
                        {
                            var articles = Helper.getContentByPrograms("" + ProgramId, 1, true);
                            list = list.Where(s => articles.Contains(s.ArticleID) ||   s.Author == userid);
                        }
                        if (FunctionId.HasValue)
                        {
                            var articles = Helper.getContentByFunctions("" + FunctionId, 1, true);
                            list = list.Where(s => articles.Contains(s.ArticleID) || s.Author == userid);
                        }
                    }
                    if (!string.IsNullOrEmpty(filter.tags))
                    {

                        var alist = Helper.getContentByTags(filter.tags, 1);

                        list = list.Where(s => alist.Contains(s.ArticleID));
                    }
                    if (!string.IsNullOrEmpty(filter.sites))
                    {
                        var alist = Helper.getContentBySites(filter.sites, 1);


                        list = list.Where(s => alist.Contains(s.ArticleID));
                    }
                    if (!string.IsNullOrEmpty(filter.programs))
                    {
                        var alist = Helper.getContentByPrograms(filter.programs, 1);


                        list = list.Where(s => alist.Contains(s.ArticleID));
                    }
                    if (!string.IsNullOrEmpty(filter.functions))
                    {
                        var alist = Helper.getContentByFunctions(filter.functions, 1);


                        list = list.Where(s => alist.Contains(s.ArticleID));
                    }

                    var total = list.Count();

                    if (perPage == 0)
                        perPage = total;

                    list = list.Skip((page - 1) * perPage).Take(perPage);


                    var tmp = new List<ArticleListDTO>();

                    foreach (var _t in list)
                    {
                        var taglist = Helper.getTagsByContent(_t.ArticleID, 1);

                        _t.tags = (taglist.Count > 0) ? String.Join(",", taglist) : "";

                        _t.files = db.ArticleFile.Where(a => a.Active && a.ArticleId == _t.ArticleID).Select(a => new
                        ArticleFileListDTO
                        {
                            id = a.id,
                            FileName = a.FileName,
                            CreatedDate = a.CreatedDate
                        }).ToList();

                        tmp.Add(_t);
                    }

                    return Ok(new { Total = total, Data = tmp.ToList() });

                }
                catch (Exception e)
                {
                    return BadRequest(e.Message);
                }

            }
        }

        [HttpPost]
        [Route("api/article/add")]
        public IHttpActionResult add(ArticleDTO o)
        {
            try
            {


                using (var db = new EHubEntities())
                {
                    var newa = new Article();

                    newa.Title = o.Title;
                    newa.Body = o.Body;
                    newa.PinOnTop = o.PinOnTop;
                    newa.PreviewText = o.PreviewText;
                    newa.PreviewImage = o.PreviewImage;
                    newa.Author = o.Author;
                    newa.AuthorName = o.AuthorName;
                    newa.ExpirationDate = o.ExpirationDate;
                    newa.BackgroundColor = o.BackgroundColor;


                    db.Article.Add(newa);
                    db.SaveChanges();

                    var newc = new Content();

                    newc.ContentTypeID = 1;
                    newc.RefID = newa.ArticleID;

                    newc.CreatedDate = o.CreatedOn;
                    newc.UpdatedDate = o.CreatedOn;
                    newc.Active = o.Active;

                    db.Content.Add(newc);
                    db.SaveChanges();



                    if (!string.IsNullOrEmpty(o.roles)) {
                        foreach (var r in o.roles.Split(new char[] { ',' })) {
                            var ra = new RoleToArticle {
                                RoleId = int.Parse(r.Trim()),
                                ArticleId = newa.ArticleID,
                                Active = true
                            };
                            db.RoleToArticle.Add(ra);


                        }
                        db.SaveChanges();

                    }
                    Helper.updateTags(o.tags, newc.ContentID);
                    Helper.updateSites(o.sites, newc.ContentID);
                    Helper.updatePrograms(o.programs, newc.ContentID);
                    Helper.updateFunctions(o.functions, newc.ContentID);

                    NotifyController.Insert(newc.ContentID, o.roles, o.CreatedOn, "New Article '" + o.Title + "'");



                    return Ok(newa.ArticleID);



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.StackTrace);
            }
        }
        [HttpPost]
        [Route("api/article/update")]
        public IHttpActionResult update(ArticleDTO o)
        {
            string UserId = "";  //30152    30580

            ClaimsIdentity identity = (ClaimsIdentity)User.Identity;
            //EmployeeId

            foreach (var cl in identity.Claims)
            {
                if (cl.Type == "EmployeeId")
                {
                    UserId = cl.Value;
                }
            }

            if (string.IsNullOrEmpty(UserId))
            {
                return BadRequest("User name is not found");
            }

            try
            {


                using (var db = new EHubEntities())
                {

                    var newa = db.Article.Where(a => a.ArticleID == o.ArticleID).FirstOrDefault();
                    if (newa == null)
                    {
                        return NotFound();
                    }
                    
                    bool issuperadmin = false;

                    var sa = db.SuperAdmin.Where(s => s.Active && "" + s.EmployeeId == UserId).FirstOrDefault();
                    if (sa != null)
                    {
                        issuperadmin = true;
                    }


                    newa.Title = o.Title;
                    newa.Body = o.Body;
                    newa.PinOnTop = o.PinOnTop;
                    newa.PreviewText = o.PreviewText;
                    newa.PreviewImage = o.PreviewImage;
                    newa.ExpirationDate = o.ExpirationDate;
                    newa.BackgroundColor = o.BackgroundColor;
                    //   newa.SiteId = o.SiteId;



                    var c = db.Content.Where(cc => cc.RefID == newa.ArticleID && cc.ContentTypeID == 1).FirstOrDefault();
                    c.UpdatedDate = o.UpdatedDate;

                    db.SaveChanges();


                    foreach (var r in db.RoleToArticle.Where(rl => rl.ArticleId == o.ArticleID).ToList())
                    {
                        db.RoleToArticle.Remove(r);
                    }
                    db.SaveChanges();
                    //                    if (!string.IsNullOrEmpty(o.roles)  &&  !issuperadmin)
                    if (!string.IsNullOrEmpty(o.roles) )
                    {
                        foreach (var r in o.roles.Split(new char[] { ',' }))
                        {
                            var ra = new RoleToArticle
                            {
                                RoleId = int.Parse(r.Trim()),
                                ArticleId = newa.ArticleID,
                                Active = true
                            };
                            db.RoleToArticle.Add(ra);


                        }
                        db.SaveChanges();

                    }

                    Helper.updateTags(o.tags, c.ContentID);



                    var rclist = db.RoleToArticle.Where(rl => rl.ArticleId == o.ArticleID).Select(rl => rl.RoleId.ToString()).ToList();

                    NotifyController.Insert(c.ContentID, string.Join(",", rclist), o.UpdatedDate, "Updated Article '" + o.Title + "'");
                    Helper.updateSites(o.sites, c.ContentID);
                    Helper.updatePrograms(o.programs, c.ContentID);
                    Helper.updateFunctions(o.functions, c.ContentID);

                    setActive(o.ArticleID, o.Active);
                    return Ok();



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.Message);
            }
        }

        [HttpGet]
        [Route("api/article/del/{id}")]
        public IHttpActionResult del(int id)
        {
            try
            {


                using (var db = new EHubEntities())
                {

                    var ar = db.Article.Where(a => a.ArticleID == id).FirstOrDefault();
                    if (ar == null)
                    {
                        return NotFound();
                    }
                    db.Article.Remove(ar);
                    var ct = db.Content.Where(c => c.RefID == id && c.ContentTypeID == 1).FirstOrDefault();
                    if (ct != null)
                    {
                        db.Content.Remove(ct);
                    }

                    db.SaveChanges();

                    foreach (var r in db.RoleToArticle.Where(rl => rl.ArticleId == id).ToList())
                    {
                        db.RoleToArticle.Remove(r);
                    }
                    db.SaveChanges();



                    Helper.deleteTags(ct.ContentID);
                    Helper.deleteSites(ct.ContentID);
                    Helper.deletePrograms(ct.ContentID);
                    Helper.deleteFunctions(ct.ContentID);

                    return Ok();



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.Message);
            }
        }

        [HttpGet]
        [Route("api/article/active/{id}/{active}")]
        public IHttpActionResult setActive(int id, bool active)
        {
            try
            {


                using (var db = new EHubEntities())
                {

                    var ar = db.Article.Where(a => a.ArticleID == id).FirstOrDefault();
                    if (ar == null)
                    {
                        return NotFound();
                    }

                    var ct = db.Content.Where(c => c.RefID == id && c.ContentTypeID == 1).FirstOrDefault();
                    if (ct == null)
                    {
                        return NotFound();
                    }
                    ct.Active = active;
                    ct.UpdatedDate = DateTime.Now;
                    db.SaveChanges();



                    return Ok();



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.Message);
            }
        }

        [HttpGet]
        [Route("api/article/pin/{id}/{pin}")]
        public IHttpActionResult setPin(int id, bool pin, DateTime ud)
        {
            try
            {


                using (var db = new EHubEntities())
                {

                    var ar = db.Article.Where(a => a.ArticleID == id).FirstOrDefault();
                    if (ar == null)
                    {
                        return NotFound();
                    }

                    ar.PinOnTop = pin;

                    var c = db.Content.Where(cc => cc.RefID == id && cc.ContentTypeID == 1).FirstOrDefault();
                    c.UpdatedDate = ud;

                    db.SaveChanges();



                    return Ok();



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.Message);
            }
        }



        [HttpGet]
        [Route("api/article/like/{id}")]
        public IHttpActionResult Like(int id)
        {
            try
            {

                var EmployeeId = ((ClaimsIdentity)User.Identity).Claims.FirstOrDefault(u => u.Type.Equals("EmployeeId")).Value;

                using (var db = new EHubEntities())
                {

                    var _l = db.ArticleLikes.Where(e => e.ArticleId == id && "" + e.EmployeeId == EmployeeId).FirstOrDefault();
                    if (_l != null)
                    {
                        db.ArticleLikes.Remove(_l);
                        db.SaveChanges();
                        return Ok();

                    }


                    var l = new ArticleLikes();

                    l.EmployeeId = int.Parse(EmployeeId);
                    l.ArticleId = id;

                    db.ArticleLikes.Add(l);
                    db.SaveChanges();

                    return Ok();



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.Message);
            }
        }


        [Route("api/article/fileupload")]
        [HttpPost]
        public async Task<IHttpActionResult> fileUpload(int ArticleId,bool IsPreview = false)
        {
            try
            {

                if (!Request.Content.IsMimeMultipartContent())
                {

                    return BadRequest("Unsupported Media Type");
                }

                if (Request.Content == null)
                {

                    return BadRequest("Invalid: missing Content");
                }

                string filename = string.Empty;

                using (var db = new EHubEntities())
                {


                    var provider = new MultipartMemoryStreamProvider();

                    await Request.Content.ReadAsMultipartAsync(provider);

                    for (int i = 0; i < provider.Contents.Count; i++)
                    {
                        var file = provider.Contents[i];
                        if (file.Headers.ContentLength > 0)
                        {
                            string fileExtension = string.Empty;

                            try
                            {

                                filename = file.Headers.ContentDisposition.FileName.Trim('\"');
                                fileExtension = Path.GetExtension(filename.ToLower());

                                string contentType = string.Empty;
                                // Word
                                if (fileExtension.Equals(".doc", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "application/msword";
                                }
                                else if (fileExtension.Equals(".docx", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
                                }
                                else if (fileExtension.Equals(".rtf", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "application/rtf";
                                }
                                // PDF
                                else if (fileExtension.Equals(".pdf", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "application/pdf";
                                }
                                // Images
                                else if (fileExtension.Equals(".jpg", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "image/jpeg";
                                }
                                else if (fileExtension.Equals(".jpeg", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "image/jpeg";
                                }
                                else if (fileExtension.Equals(".gif", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "image/gif";
                                }
                                else if (fileExtension.Equals(".bmp", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "image/bmp";
                                }
                                else if (fileExtension.Equals(".png", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "image/png";
                                }
                                else if (fileExtension.Equals(".tif", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "image/tiff";
                                }
                                else if (fileExtension.Equals(".tiff", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "image/tiff";
                                }
                                else if (fileExtension.Equals(".mp4", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "video/mp4";
                                }
                                else if (fileExtension.Equals(".mp3", StringComparison.OrdinalIgnoreCase))
                                {
                                    contentType = "audio/mpeg";
                                }


                                if (contentType == string.Empty)
                                {
                                   // return BadRequest("Invalid File Type");
                                }

                                var buffer = await file.ReadAsByteArrayAsync();

                                try
                                {

                                    var fileUpload = new ArticleFile();
                                    fileUpload.Active = true;
                                    fileUpload.ArticleId = ArticleId;

                                    fileUpload.FileName = filename;
                                    fileUpload.FileData = buffer;
                                    fileUpload.CreatedDate = DateTime.Now;
                                    fileUpload.FileType = contentType;

                                    db.ArticleFile.Add(fileUpload);
                                    db.SaveChanges();

                                    if (IsPreview)
                                    {
                                        var a = db.Article.Where(o => o.ArticleID == ArticleId).FirstOrDefault();
                                        a.PreviewFile = fileUpload.id;
                                        db.SaveChanges();
                                    }


                                    return Ok(fileUpload.id);
                                }
                                catch (Exception ex)
                                {
                                    return BadRequest(ex.Message);
                                }
                            }
                            catch (Exception ex)
                            {
                                return BadRequest(ex.Message);
                            }

                        }



                    }



                }

                return Ok();

            }
            catch (Exception ex)
            {

                return BadRequest(ex.Message);
            }
        }

        [HttpGet]
        [Route("api/article/filelist/{article_id}")]
        public IHttpActionResult FileList(int article_id)
        {
            try
            {


                using (var db = new EHubEntities())
                {

                    var list = db.ArticleFile.Where(a => a.Active && a.ArticleId == article_id).Select(a => new
                    ArticleFileListDTO
                     {
                        id = a.id,
                        FileName = a.FileName,
                        CreatedDate = a.CreatedDate
                    });



                    return Ok(list.ToList());



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.Message);
            }
        }
        [HttpGet]
        [Route("api/article/filedelete/{id}")]
        public IHttpActionResult FileDelete(int id)
        {
            try
            {


                using (var db = new EHubEntities())
                {

                    var f = db.ArticleFile.Where(fl => fl.id == id).FirstOrDefault();
                    if (f == null)
                    {
                        return NotFound();
                    }

                    db.ArticleFile.Remove(f);
                    db.SaveChanges();
                    var a = db.Article.Where(o => o.PreviewFile.HasValue &&  o.PreviewFile.Value == id).FirstOrDefault();
                    if(a  != null)
                    {
                        a.PreviewFile = null;
                        db.SaveChanges();
                    }
                    return Ok();



                }
            }
            catch (Exception e)
            {
                return BadRequest(e.Message);
            }
        }

    }



    public class ArticleDTO
    {
        public int ArticleID { get; set; }
        public string Title { get; set; }
        public string Body { get; set; }
        public bool PinOnTop { get; set; }
        public DateTime? ExpirationDate { get; set; }
        public DateTime CreatedOn { get; set; }
        public DateTime UpdatedDate { get; set; }

        public bool Active { get; set; }

        public string PreviewText { get; set; }
        public string PreviewImage { get; set; }
        public int Author { get; set; }
        public string AuthorName { get; set; }
        public string roles { get; set; }
        public string BackgroundColor { get; set; }
        public string tags { get; set; }
        public string sites { get; set; }
        public string programs { get; set; }
        public string functions { get; set; }


    }
    public class ArticleFilterDTO
    {
        public bool showall { get; set; }
        public int Author { get; set; }
        public string roles { get; set; }
        public string tags { get; set; }
        public string sites { get; set; }
        public string programs { get; set; }
        public string functions { get; set; }
        public string search { get; set; }


    }


    public class ArticleListDTO
    {
        public ArticleListDTO()
        {
            this.files = new List<ArticleFileListDTO>();
        }
        public int ArticleID { get; set; }
        public string Title { get; set; }
        public string Body { get; set; }
        public bool PinOnTop { get; set; }
        public DateTime? ExpirationDate { get; set; }
        public DateTime CreatedDate { get; set; }
        public DateTime? UpdatedDate { get; set; }

        public bool Active { get; set; }

        public string PreviewText { get; set; }
        public string PreviewImage { get; set; }
        public int Author { get; set; }
        public string AuthorName { get; set; }
        public string roles { get; set; }
        public string BackgroundColor { get; set; }
        public string tags { get; set; }
        public string sites { get; set; }
        public string programs { get; set; }
        public string functions { get; set; }
        
        public bool like { get; set; }
        public int likes { get; set; }

        public List<ArticleFileListDTO> files { get; set; }
        public int? PreviewFile { get; set; }

    }

    public class ArticleFileListDTO
    {
        public int id { get; set; }
        public string FileName { get; set; }
        public DateTime CreatedDate { get; set; }

    }
}
 